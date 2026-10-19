import type { LingohubResourceFile, LingohubTranslationStatus } from "../types";
import { usePanel } from "kirbyuse";
import mitt from "mitt";
import {
  PLUGIN_RECEIVE_API_ROUTE,
  PLUGIN_SEND_API_ROUTE,
  PLUGIN_STATUS_API_ROUTE,
} from "../constants";
import { useModel } from "./model";
import { usePluginContext } from "./plugin";

const UTF8_SUFFIX_RE = /\.utf-?8$/i;

const emitter = mitt<{ translationUpdate: void }>();

export function useLingohub() {
  const panel = usePanel();
  const { getModelData } = useModel();

  async function resolveResource(languageCode: string) {
    const { languages } = await usePluginContext();
    const model = await getModelData();

    let localeCode = languages?.[languageCode]?.locale?.[0] ?? languageCode;

    // Support ISO 3166-1 Alpha-2 and ISO 639-1 codes:
    // (1) Convert locale code to IETF language tag format (e.g., `en_US` to `en-US`).
    localeCode = localeCode.replaceAll("_", "-");

    // (2) Remove UTF-8 suffix for consistency.
    localeCode = localeCode.replace(UTF8_SUFFIX_RE, "");

    let blueprintName = model.blueprint.name;

    if (
      blueprintName.startsWith("pages/") ||
      blueprintName.startsWith("files/")
    ) {
      blueprintName = blueprintName.substring(6);
    }

    const filename = `${blueprintName}_${localeCode}.json`;
    const resourcePath = `${model.id}/${filename}`;

    return {
      filename,
      resourcePath,
    };
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

  async function getTranslationResources(
    status: LingohubTranslationStatus | undefined,
    languageCode: string,
  ): Promise<LingohubResourceFile[]> {
    const { resourcePath } = await resolveResource(languageCode);

    // Lingohub may return the resource filename with different casing.
    const resource =
      status?.resourceFiles?.find((item) =>
        item.files.some((file) => file.name === resourcePath),
      ) ??
      status?.resourceFiles?.find((item) =>
        item.files.some(
          (file) => file.name.toLowerCase() === resourcePath.toLowerCase(),
        ),
      );

    return resource?.files ?? [];
  }

  async function getTranslationResourceFile(
    status: LingohubTranslationStatus | undefined,
    languageCode: string,
  ): Promise<LingohubResourceFile | undefined> {
    const { resourcePath } = await resolveResource(languageCode);
    const resourceFiles = await getTranslationResources(status, languageCode);

    // Lingohub may return the resource filename with different casing.
    return (
      resourceFiles.find((file) => file.name === resourcePath) ??
      resourceFiles.find(
        (file) => file.name.toLowerCase() === resourcePath.toLowerCase(),
      )
    );
  }

  async function uploadTranslation(languageCode: string) {
    const defaultLanguageData = await getModelData();

    await panel.api.post(
      PLUGIN_SEND_API_ROUTE,
      {
        id: defaultLanguageData.id ?? "site",
        languageCode,
      },
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
    const defaultLanguageData = await getModelData();

    await panel.api.post(
      PLUGIN_RECEIVE_API_ROUTE,
      {
        id: defaultLanguageData.id ?? "site",
        languageCode,
        targetStatus,
      },
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
    emitter,
    getTranslationStatus,
    getTranslationResources,
    getTranslationResourceFile,
    uploadTranslation,
    downloadTranslation,
  };
}
