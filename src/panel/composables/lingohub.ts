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

  /**
   * Throws when the plugin is misconfigured or Lingohub refuses the request,
   * so each caller can show why there is no status.
   */
  async function getTranslationStatus(): Promise<LingohubTranslationStatus> {
    const configError = await getConfigError();
    if (configError) throw new Error(configError);

    const { translationStatus } = await panel.api.get(
      PLUGIN_STATUS_API_ROUTE,
      undefined,
      undefined,
      // Avoid showing the Panel loading indicator.
      true,
    );

    return translationStatus;
  }

  /**
   * Returns every language file of the resource the given file belongs to.
   * Lingohub may return the filename with different casing.
   */
  function findResourceFiles(
    status: LingohubTranslationStatus,
    resourceFile: string | undefined,
  ): LingohubResourceFile[] {
    const name = resourceFile?.toLowerCase();

    return (
      status.resourceFiles?.find((resource) =>
        resource.files.some((file) => file.name.toLowerCase() === name),
      )?.files ?? []
    );
  }

  function findResourceFile(
    status: LingohubTranslationStatus,
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

  /**
   * Lingohub imports an upload in the background, without a job to wait on.
   * Resolves once every given file has segments newer than in `previousStatus`,
   * so the status reflects the upload – or after about 30 seconds, or at the
   * first failed status request, which the refresh after the upload reports.
   */
  async function waitForImport(
    previousStatus: LingohubTranslationStatus,
    resourceFiles: string[],
  ) {
    const previousUpdates = resourceFiles.map(
      (name) => findResourceFile(previousStatus, name)?.segmentsUpdatedAt,
    );

    for (let attempt = 0; attempt < 15; attempt++) {
      await new Promise((resolve) => setTimeout(resolve, 2000));
      const status = await getTranslationStatus().catch(() => undefined);
      if (!status) return;

      const isImported = resourceFiles.every((name, index) => {
        const updatedAt = findResourceFile(status, name)?.segmentsUpdatedAt;
        return updatedAt && updatedAt !== previousUpdates[index];
      });

      if (isImported) return;
    }
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

  async function getConfigError() {
    const { config } = await usePluginContext();
    const optionStates = {
      apiKey: config.hasApiKey,
      workspaceId: config.hasWorkspaceId,
      projectId: config.hasProjectId,
    };
    const missingOption = Object.entries(optionStates).find(
      ([, isSet]) => !isSet,
    )?.[0];

    return !panel.multilang
      ? panel.t("johannschopplich.lingohub.error.multilang")
      : missingOption
        ? panel.t("johannschopplich.lingohub.error.missingOption", {
            option: `johannschopplich.lingohub.${missingOption}`,
          })
        : undefined;
  }

  return {
    getModelContext,
    getTranslationStatus,
    findResourceFiles,
    findResourceFile,
    isFullyApproved,
    uploadTranslation,
    waitForImport,
    downloadTranslation,
  };
}
