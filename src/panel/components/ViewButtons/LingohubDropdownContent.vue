<script>
import { ref, useDialog, usePanel } from "kirbyuse";
import { useLingohub } from "../../composables/lingohub";
import { useModel } from "../../composables/model";
import { PLUGIN_MODEL_CONTEXT_API_ROUTE } from "../../constants";

export default {
  inheritAttrs: false,
};
</script>

<script setup>
const panel = usePanel();
const { openFieldsDialog } = useDialog();
const { getViewModelData } = useModel();
const {
  emitter,
  getTranslationStatus,
  getTranslationResourceFile,
  uploadTranslation,
  downloadTranslation,
} = useLingohub();

const defaultLanguage = panel.languages.find((language) => language.default);
const translationLanguages = panel.languages.filter(
  (language) => !language.default,
);

const modelContext = ref();

// Lazily fetch model data for the default language
const initializationPromise = (async () => {
  const defaultLanguageData = await getViewModelData();

  // Check which translations are available
  modelContext.value = await panel.api.post(PLUGIN_MODEL_CONTEXT_API_ROUTE, {
    id: defaultLanguageData.id ?? "site",
  });
})();

async function invokeWhenInitialized(fn) {
  await initializationPromise;
  fn?.();
}

async function uploadTranslations() {
  const { availableTranslationLanguageCodes } = modelContext.value;
  panel.view.isLoading = true;

  const status = await getTranslationStatus();

  // Find all translation codes that already exist on Lingohub
  const localizedLanguageCodes = (
    await Promise.all(
      availableTranslationLanguageCodes.map(async (languageCode) => {
        const resourceFile = await getTranslationResourceFile(
          status,
          languageCode,
        );
        const hasAnyTranslation = resourceFile
          ? resourceFile.statuses.TRANSLATED > 0 ||
            resourceFile.statuses.DRAFT > 0 ||
            resourceFile.statuses.APPROVED > 0
          : false;

        if (hasAnyTranslation) {
          return;
        }

        return languageCode;
      }),
    )
  ).filter(Boolean);

  const options = await openFieldsDialog({
    submitButton: {
      icon: "upload",
      theme: "positive",
      text: panel.t("johannschopplich.lingohub.upload"),
    },
    fields: {
      sourceLanguage: {
        type: "checkboxes",
        label: panel.t("johannschopplich.lingohub.sourceLanguage"),
        options: [
          {
            value: defaultLanguage.code,
            text: defaultLanguage.name,
          },
        ],
      },
      targetLanguages:
        availableTranslationLanguageCodes.length > 0
          ? {
              type: "checkboxes",
              label: panel.t("johannschopplich.lingohub.targetLanguages"),
              options: availableTranslationLanguageCodes.map(
                (translationCode) => ({
                  value: translationCode,
                  text:
                    panel.languages.find(
                      (language) => language.code === translationCode,
                    )?.name ?? translationCode,
                }),
              ),
            }
          : {
              type: "info",
              theme: "empty",
              label: panel.t("johannschopplich.lingohub.targetLanguages"),
              text: panel.t("johannschopplich.lingohub.emptyKirbyTranslations"),
            },
    },
    value: {
      sourceLanguage: [defaultLanguage.code],
      targetLanguages: localizedLanguageCodes,
    },
  });

  if (!options) return;

  panel.view.isLoading = true;

  try {
    for (const languageCode of [
      ...options.sourceLanguage,
      ...options.targetLanguages,
    ]) {
      await uploadTranslation(languageCode);
    }

    emitter.emit("translationUpdate");

    panel.notification.success(
      panel.t("johannschopplich.lingohub.success.upload"),
    );
  } catch (error) {
    console.error(error);
    panel.notification.error(error.message);
  } finally {
    panel.view.isLoading = false;
  }
}

async function downloadTranslations() {
  const status = await getTranslationStatus();
  const defaultResourceFile = await getTranslationResourceFile(
    status,
    defaultLanguage.code,
  );

  // Find all languages that are 100% translated and approved
  const approvedLanguageCodes = (
    await Promise.all(
      translationLanguages.map(async (language) => {
        const resourceFile = await getTranslationResourceFile(
          status,
          language.code,
        );

        if (
          resourceFile &&
          resourceFile.statuses.APPROVED >=
            defaultResourceFile.statuses.APPROVED
        ) {
          return language.code;
        }
      }),
    )
  ).filter(Boolean);

  const options = await openFieldsDialog({
    submitButton: {
      icon: "import",
      theme: "positive",
      text: panel.t("johannschopplich.lingohub.download"),
    },
    fields: {
      status: {
        type: "select",
        label: panel.t("johannschopplich.lingohub.status"),
        options: [
          {
            value: "ALL",
            text: "All",
          },
          {
            value: "TRANSLATED_AND_APPROVED",
            text: "Translated and approved",
          },
          {
            value: "APPROVED",
            text: "Approved",
          },
        ],
      },
      languages: {
        type: "checkboxes",
        label: panel.t("johannschopplich.lingohub.languages"),
        options: translationLanguages.map((language) => ({
          value: language.code,
          text: language.name,
        })),
      },
    },
    value: {
      status: "APPROVED",
      languages: approvedLanguageCodes,
    },
  });

  if (!options) return;

  panel.view.isLoading = true;

  try {
    for (const languageCode of options.languages) {
      await downloadTranslation(languageCode, options.status);
    }

    await panel.view.reload();

    emitter.emit("translationUpdate");

    panel.notification.success(
      panel.t("johannschopplich.lingohub.success.download"),
    );
  } catch (error) {
    panel.view.isLoading = false;
    console.error(error);
    panel.notification.error(error.message);
  }
}
</script>

<template>
  <div>
    <k-dropdown-item
      icon="lingohub-upload-cloud"
      @click="invokeWhenInitialized(uploadTranslations)"
    >
      {{ panel.t("johannschopplich.lingohub.upload") }}
    </k-dropdown-item>
    <hr />
    <k-dropdown-item
      icon="lingohub-download-cloud"
      @click="invokeWhenInitialized(downloadTranslations)"
    >
      {{ panel.t("johannschopplich.lingohub.download") }}
    </k-dropdown-item>
  </div>
</template>
