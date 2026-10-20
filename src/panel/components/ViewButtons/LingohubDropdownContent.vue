<script lang="ts">
import { useDialog, usePanel } from "kirbyuse";
import { useLingohub } from "../../composables/lingohub";
import { TRANSLATION_UPDATE_EVENT } from "../../constants";

export default {
  inheritAttrs: false,
};
</script>

<script setup lang="ts">
defineProps<{
  editorUrl?: string;
}>();

const panel = usePanel();
const { openFieldsDialog } = useDialog();
const {
  getModelContext,
  getTranslationStatus,
  findResourceFile,
  isFullyApproved,
  uploadTranslation,
  waitForImport,
  downloadTranslation,
} = useLingohub();

const defaultLanguage = panel.languages.find((language) => language.default)!;
const translationLanguages = panel.languages.filter(
  (language) => !language.default,
);

async function loadDialogContext() {
  panel.view.isLoading = true;

  try {
    const [status, modelContext] = await Promise.all([
      getTranslationStatus(),
      getModelContext(),
    ]);

    return { status, ...modelContext };
  } catch (error) {
    console.error(error);
    panel.notification.error((error as Error).message);
  } finally {
    panel.view.isLoading = false;
  }
}

async function uploadTranslations() {
  const context = await loadDialogContext();
  if (!context) return;

  const { status, resourceFiles, translatedLanguageCodes } = context;

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

  const languageCodes = [...options.sourceLanguage, ...options.targetLanguages];

  panel.view.isLoading = true;

  try {
    for (const languageCode of languageCodes) {
      await uploadTranslation(languageCode);
    }

    panel.notification.success(
      panel.t("johannschopplich.lingohub.success.upload"),
    );
  } catch (error) {
    console.error(error);
    panel.notification.error((error as Error).message);
    return;
  } finally {
    panel.view.isLoading = false;
  }

  // Outside the loading state, so the editor keeps working while Lingohub imports.
  await waitForImport(
    status,
    languageCodes.map((languageCode) => resourceFiles[languageCode]!),
  );

  panel.events.emit(TRANSLATION_UPDATE_EVENT);
}

async function downloadTranslations() {
  const context = await loadDialogContext();
  if (!context) return;

  const { status, resourceFiles } = context;

  // Preselect the languages whose segments are all approved.
  const approvedLanguageCodes = translationLanguages
    .filter((language) =>
      isFullyApproved(findResourceFile(status, resourceFiles[language.code])),
    )
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
            text: panel.t("johannschopplich.lingohub.status.all"),
          },
          {
            value: "TRANSLATED_AND_APPROVED",
            text: panel.t(
              "johannschopplich.lingohub.status.translatedAndApproved",
            ),
          },
          {
            value: "APPROVED",
            text: panel.t("johannschopplich.lingohub.status.approved"),
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

    panel.events.emit(TRANSLATION_UPDATE_EVENT);

    panel.notification.success(
      panel.t("johannschopplich.lingohub.success.download"),
    );
  } catch (error) {
    console.error(error);
    panel.notification.error((error as Error).message);
  } finally {
    panel.view.isLoading = false;
  }
}
</script>

<template>
  <div>
    <k-dropdown-item icon="lingohub-upload-cloud" @click="uploadTranslations()">
      {{ panel.t("johannschopplich.lingohub.upload") }}
    </k-dropdown-item>
    <hr />
    <k-dropdown-item
      icon="lingohub-download-cloud"
      @click="downloadTranslations()"
    >
      {{ panel.t("johannschopplich.lingohub.download") }}
    </k-dropdown-item>
    <template v-if="editorUrl">
      <hr />
      <k-dropdown-item icon="open" :link="editorUrl" target="_blank">
        {{ panel.t("johannschopplich.lingohub.openInLingohub") }}
      </k-dropdown-item>
    </template>
  </div>
</template>
