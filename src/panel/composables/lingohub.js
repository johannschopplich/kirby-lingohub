import { usePanel } from "kirbyuse";
import { ofetch } from "ofetch";
import { PLUGIN_RECEIVE_API_ROUTE, PLUGIN_SEND_API_ROUTE } from "../constants";
import { useModel } from "./model";
import { usePluginContext } from "./plugin";

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
    resolveResource,
    getTranslationStatus,
    getTranslationResources,
    getTranslationResourceFile,
    uploadTranslation,
    downloadTranslation,
  };
}

// Example status response from Lingohub API:
// {
//   "resourceFiles": [
//     {
//       "uuid": "rc_12Mgm0CxW9X1-227535",
//       "basename": "default%{language_code_separator}%{locale}.json",
//       "filePathWithLocalePlaceholder": "home/default%{language_code_separator}%{locale}.json",
//       "files": [
//         {
//           "name": "home/default_en-US.json",
//           "languageCode": "en",
//           "mimeType": "text/json",
//           "statuses": {
//             "NOT_TRANSLATED": 0,
//             "TRANSLATED": 0,
//             "DRAFT": 0,
//             "APPROVED": 13
//           },
//           "segmentsUpdatedAt": "2025-01-15T18:30:20.901+00:00",
//           "failingLingochecksCount": 0
//         },
//         {
//           "name": "home/default_de.json",
//           "languageCode": "de",
//           "mimeType": "text/json",
//           "statuses": {
//             "NOT_TRANSLATED": 13,
//             "TRANSLATED": 0,
//             "DRAFT": 0,
//             "APPROVED": 0
//           },
//           "segmentsUpdatedAt": "2025-01-15T16:34:58.218+00:00",
//           "failingLingochecksCount": 0
//         },
//         {
//           "name": "home/default_pt-BR.json",
//           "languageCode": "pt-BR",
//           "mimeType": "text/json",
//           "statuses": {
//             "NOT_TRANSLATED": 13,
//             "TRANSLATED": 0,
//             "DRAFT": 0,
//             "APPROVED": 0
//           },
//           "segmentsUpdatedAt": "2025-01-15T16:34:58.218+00:00",
//           "failingLingochecksCount": 0
//         }
//       ]
//     }
//   ],
//   "supportedExportFormats": [
//     {
//       "id": "json.standard",
//       "name": "Standard JSON"
//     }
//   ],
//   "repositoryConnections": [
//     {
//       "provider": "github",
//       "connected": false
//     },
//     {
//       "provider": "bitbucket",
//       "connected": false
//     },
//     {
//       "provider": "gitlab",
//       "connected": false
//     },
//     {
//       "provider": "azure",
//       "connected": false
//     }
//   ],
//   "repositoryConnected": false,
//   "pushToRepositoryPossible": false,
//   "pushType": null,
//   "oneFileForAllLocales": false,
//   "segmentsUpdatedAt": "2025-01-15T18:30:20.901+00:00"
// }
