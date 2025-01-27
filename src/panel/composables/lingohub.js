import { usePanel } from "kirbyuse";
import mitt from "mitt";
import { ofetch } from "ofetch";
import { PLUGIN_RECEIVE_API_ROUTE, PLUGIN_SEND_API_ROUTE } from "../constants";
import { useModel } from "./model";
import { usePluginContext } from "./plugin";

const emitter = mitt();

export function useLingohub() {
  const panel = usePanel();
  const { getDefaultLanguageData } = useModel();

  const create$lingohub = async () =>
    ofetch.create({
      baseURL: "https://api.lingohub.com/v1",
      headers: {
        Authorization: `Bearer ${(await usePluginContext()).config.apiKey}`,
      },
    });

  async function resolveResource(languageCode) {
    const { languages } = await usePluginContext();
    const model = await getDefaultLanguageData();

    let localeCode = languages?.[languageCode]?.locale?.[0] ?? languageCode;

    // Support ISO 3166-1 Alpha-2 and ISO 639-1 codes:
    // (1) Convert locale code to IETF language tag format (e.g., `en_US` to `en-US`)
    localeCode = localeCode.replace("_", "-");

    // (2) Remove UTF-8 suffix and convert to lowercase for consistency
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
    const { config } = await usePluginContext();
    const $lingohub = await create$lingohub();

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
    const resource = status?.resourceFiles?.find((item) =>
      item.files.some((file) => file.name === resourcePath),
    );
    return resource?.files ?? [];
  }

  async function getTranslationResourceFile(status, languageCode) {
    const { resourcePath } = await resolveResource(languageCode);
    const resourceFiles = await getTranslationResources(status, languageCode);
    return resourceFiles.find((file) => file.name === resourcePath);
  }

  async function uploadTranslation(languageCode) {
    const defaultLanguageData = await getDefaultLanguageData();

    await panel.api.post(
      PLUGIN_SEND_API_ROUTE,
      {
        id: defaultLanguageData.id ?? "site",
        languageCode,
      },
      undefined,
      // Silent
      true,
    );
  }

  async function downloadTranslation(languageCode, targetStatus) {
    const defaultLanguageData = await getDefaultLanguageData();

    await panel.api.post(
      PLUGIN_RECEIVE_API_ROUTE,
      {
        id: defaultLanguageData.id ?? "site",
        languageCode,
        targetStatus,
      },
      undefined,
      // Silent
      true,
    );
  }

  return {
    emitter,
    resolveResource,
    getTranslationStatus,
    getTranslationResources,
    getTranslationResourceFile,
    uploadTranslation,
    downloadTranslation,
  };
}
