import { usePanel } from "kirbyuse";
import mitt from "mitt";
import { ofetch } from "ofetch";
import { PLUGIN_RECEIVE_API_ROUTE, PLUGIN_SEND_API_ROUTE } from "../constants";
import { useModel } from "./model";
import { usePluginContext } from "./plugin";

const emitter = mitt();

export function useLingohub() {
  const panel = usePanel();
  const { getModelData } = useModel();

  const create$Lingohub = async () =>
    ofetch.create({
      baseURL: "https://api.lingohub.com/v1",
      headers: {
        Authorization: `Bearer ${(await usePluginContext()).config.apiKey}`,
      },
    });

  async function resolveResource(languageCode) {
    const { languages } = await usePluginContext();
    const model = await getModelData();

    // Get the locale for the language
    let localeCode = languages?.[languageCode]?.locale?.[0] ?? languageCode;

    // Support ISO 3166-1 Alpha-2 and ISO 639-1 codes:
    // (1) Convert locale code to IETF language tag format (e.g., `en_US` to `en-US`)
    localeCode = localeCode.replaceAll("_", "-");

    // (2) Remove UTF-8 suffix for consistency
    localeCode = localeCode.replace(/\.utf-?8$/i, "");

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

  async function getTranslationStatus() {
    if (!(await validateLingohubConfig())) return;

    const { config } = await usePluginContext();
    const $lingohub = await create$Lingohub();

    try {
      return await $lingohub(
        `${config.workspaceId}/projects/${config.projectId}/status`,
      );
    } catch (error) {
      console.error(error);
      panel.notification.error(error.message);
    }
  }

  async function getTranslationResources(status, languageCode) {
    const { resourcePath } = await resolveResource(languageCode);

    // Try exact match first, then fall back to case-insensitive match
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

  async function getTranslationResourceFile(status, languageCode) {
    const { resourcePath } = await resolveResource(languageCode);
    const resourceFiles = await getTranslationResources(status, languageCode);

    // Try exact match first, then fall back to case-insensitive match
    return (
      resourceFiles.find((file) => file.name === resourcePath) ??
      resourceFiles.find(
        (file) => file.name.toLowerCase() === resourcePath.toLowerCase(),
      )
    );
  }

  async function uploadTranslation(languageCode) {
    const defaultLanguageData = await getModelData();

    await panel.api.post(
      PLUGIN_SEND_API_ROUTE,
      {
        id: defaultLanguageData.id ?? "site",
        languageCode,
      },
      undefined,
      // Avoid showing Panel loading indicator
      true,
    );
  }

  async function downloadTranslation(languageCode, targetStatus) {
    const defaultLanguageData = await getModelData();

    await panel.api.post(
      PLUGIN_RECEIVE_API_ROUTE,
      {
        id: defaultLanguageData.id ?? "site",
        languageCode,
        targetStatus,
      },
      undefined,
      // Avoid showing Panel loading indicator
      true,
    );
  }

  async function validateLingohubConfig() {
    const context = await usePluginContext();

    try {
      if (!panel.multilang) {
        throw new Error(
          "The Lingohub plugin requires a multi-language Kirby installation.",
        );
      } else if (!context.config.apiKey) {
        throw new Error(
          'Missing API key in the "johannschopplich.lingohub.apiKey" plugin option.',
        );
      } else {
        for (const key of ["workspaceId", "projectId"]) {
          if (typeof context.config[key] !== "string" || !context.config[key]) {
            throw new TypeError(
              `Missing "johannschopplich.lingohub.${key}" plugin option.`,
            );
          }
        }
      }

      return true;
    } catch (error) {
      console.error(error);
      panel.notification.error(error.message);
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
