<script lang="ts">
import type { ModelContext } from "../../types";
import { ref, useDialog, usePanel } from "kirbyuse";
import { useLingohub } from "../../composables/lingohub";

export default {
  inheritAttrs: false,
};
</script>

<script setup lang="ts">
const panel = usePanel();
const { openFieldsDialog } = useDialog();
const {
  emitter,
  getModelContext,
  getTranslationStatus,
  findResourceFile,
  uploadTranslation,
  downloadTranslation,
} = useLingohub();

const defaultLanguage = panel.languages.find((language) => language.default)!;
const translationLanguages = panel.languages.filter(
  (language) => !language.default,
);

const modelContext = ref<ModelContext>();

// Start fetching right away, `invokeWhenInitialized()` awaits it before any dropdown action runs.
const initializationPromise = (async () => {
  modelContext.value = await getModelContext();
})();

async function invokeWhenInitialized(fn: () => unknown) {
  await initializationPromise;
  fn?.();
}

async function uploadTranslations() {
  const { resourceFiles, translatedLanguageCodes } = modelContext.value!;
  panel.view.isLoading = true;

  const status = await getTranslationStatus();

  // Preselect the languages that carry no Lingohub translation yet.
  const localizedLanguageCodes = translatedLanguageCodes.filter(
    (languageCode) => {
      const statuses = findResourceFile(
        status,
        resourceFiles[languageCode],
      )?.statuses;

      return !(
        statuses &&
        (statuses.TRANSLATED > 0 || statuses.DRAFT > 0 || statuses.APPROVED > 0)
      );
    },
  );

  const options = await openFieldsDialog<{
    sourceLanguage: string[];
    targetLanguages: string[];
  }>({
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
        translatedLanguageCodes.length > 0
          ? {
              type: "checkboxes",
              label: panel.t("johannschopplich.lingohub.targetLanguages"),
              options: translatedLanguageCodes.map((translationCode) => ({
                value: translationCode,
                text:
                  panel.languages.find(
                    (language) => language.code === translationCode,
                  )?.name ?? translationCode,
              })),
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
    panel.notification.error((error as Error).message);
  } finally {
    panel.view.isLoading = false;
  }
}

async function downloadTranslations() {
  const { resourceFiles } = modelContext.value!;
  const status = await getTranslationStatus();
  const defaultResourceFile = findResourceFile(
    status,
    resourceFiles[defaultLanguage.code],
  );

  // Preselect the languages whose approved segments have caught up with the source language.
  const approvedLanguageCodes = translationLanguages
    .filter((language) => {
      const resourceFile = findResourceFile(
        status,
        resourceFiles[language.code],
      );

      return (
        resourceFile &&
        resourceFile.statuses.APPROVED >= defaultResourceFile!.statuses.APPROVED
      );
    })
    .map((language) => language.code);

  const options = await openFieldsDialog<{
    status: string;
    languages: string[];
  }>({
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
    panel.notification.error((error as Error).message);
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
