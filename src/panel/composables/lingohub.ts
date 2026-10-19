import type {
  LingohubResourceFile,
  LingohubTranslationStatus,
  ModelContext,
} from "../types";
import { usePanel } from "kirbyuse";
import {
  PLUGIN_MODEL_CONTEXT_API_ROUTE,
  PLUGIN_RECEIVE_API_ROUTE,
  PLUGIN_SEND_API_ROUTE,
  PLUGIN_STATUS_API_ROUTE,
} from "../constants";
import { usePluginContext } from "./plugin";

export function useLingohub() {
  const panel = usePanel();

  /** Resolves the current view's model on the server, see `Content::fromPath()`. */
  async function getModelContext(): Promise<ModelContext> {
    return panel.api.post(
      PLUGIN_MODEL_CONTEXT_API_ROUTE,
      { path: panel.view.path },
      undefined,
      "POST",
      // Avoid showing the Panel loading indicator.
      true,
    );
  }

  async function getTranslationStatus(): Promise<
    LingohubTranslationStatus | undefined
  > {
    if (!(await validateLingohubConfig())) return;

    try {
      const { translationStatus } = await panel.api.get(
        PLUGIN_STATUS_API_ROUTE,
        undefined,
        undefined,
        // Avoid showing the Panel loading indicator.
        true,
      );

      return translationStatus;
    } catch (error) {
      console.error(error);
      panel.notification.error((error as Error).message);
    }
  }

  /**
   * Returns every language file of the resource the given file belongs to.
   * Lingohub may return the filename with different casing.
   */
  function findResourceFiles(
    status: LingohubTranslationStatus | undefined,
    resourceFile: string | undefined,
  ): LingohubResourceFile[] {
    const name = resourceFile?.toLowerCase();

    return (
      status?.resourceFiles?.find((resource) =>
        resource.files.some((file) => file.name.toLowerCase() === name),
      )?.files ?? []
    );
  }

  function findResourceFile(
    status: LingohubTranslationStatus | undefined,
    resourceFile: string | undefined,
  ): LingohubResourceFile | undefined {
    const name = resourceFile?.toLowerCase();

    return findResourceFiles(status, resourceFile).find(
      (file) => file.name.toLowerCase() === name,
    );
  }

  function isFullyApproved(file: LingohubResourceFile | undefined) {
    if (!file) return false;

    const segmentCount = Object.values(file.statuses).reduce(
      (sum, count) => sum + count,
      0,
    );

    return segmentCount > 0 && file.statuses.APPROVED === segmentCount;
  }

  async function uploadTranslation(languageCode: string) {
    await panel.api.post(
      PLUGIN_SEND_API_ROUTE,
      { path: panel.view.path, languageCode },
      undefined,
      "POST",
      // Avoid showing the Panel loading indicator.
      true,
    );
  }

  async function downloadTranslation(
    languageCode: string,
    targetStatus: string,
  ) {
    await panel.api.post(
      PLUGIN_RECEIVE_API_ROUTE,
      { path: panel.view.path, languageCode, targetStatus },
      undefined,
      "POST",
      // Avoid showing the Panel loading indicator.
      true,
    );
  }

  async function validateLingohubConfig() {
    const { config } = await usePluginContext();

    try {
      if (!panel.multilang) {
        throw new Error(
          "The Lingohub plugin requires a multi-language Kirby installation.",
        );
      } else if (!config.hasApiKey) {
        throw new Error(
          'Missing API key in the "johannschopplich.lingohub.apiKey" plugin option.',
        );
      } else if (!config.hasWorkspaceId) {
        throw new Error(
          'Missing "johannschopplich.lingohub.workspaceId" plugin option.',
        );
      } else if (!config.hasProjectId) {
        throw new Error(
          'Missing "johannschopplich.lingohub.projectId" plugin option.',
        );
      }

      return true;
    } catch (error) {
      console.error(error);
      panel.notification.error((error as Error).message);
      return false;
    }
  }

  return {
    getModelContext,
    getTranslationStatus,
    findResourceFiles,
    findResourceFile,
    isFullyApproved,
    uploadTranslation,
    downloadTranslation,
  };
}
