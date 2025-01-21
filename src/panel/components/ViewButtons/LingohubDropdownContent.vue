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
const emit = defineEmits(["translationUpdate"]);

const panel = usePanel();
const { openTextDialog, openFieldsDialog } = useDialog();
const { getDefaultLanguageData } = useModel();
const {
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
  const defaultLanguageData = await getDefaultLanguageData();

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

  try {
    // If any Kirby translations exist, ask the user if they want to upload them
    for (const translationCode of availableTranslationLanguageCodes) {
      const resourceFile = await getTranslationResourceFile(
        status,
        translationCode,
      );
      const hasAnyTranslation = resourceFile.statuses.TRANSLATED > 0;

      // If Kirby translation exist that is not present on Lingohub, upload it automatically
      if (!hasAnyTranslation) {
        await uploadTranslation(translationCode);
      }
      // Otherwise, ask the user if they want to overwrite the existing remote translation
      else {
        const isOk = await openTextDialog(
          panel.t("johannschopplich.lingohub.dialog.overwrite", {
            language: translationCode,
          }),
        );

        await Promise.all(
          [
            isOk && uploadTranslation(translationCode),
            new Promise((resolve) => setTimeout(resolve, 250)),
          ].filter(Boolean),
        );
      }
    }

    // Always upload the default language content
    await uploadTranslation(defaultLanguage.code);

    emit("translationUpdate");

    panel.notification.success(
      panel.t("johannschopplich.lingohub.success.upload"),
    );
  } catch (error) {
    panel.view.isLoading = false;
    console.error(error);
    panel.notification.error(error.message);
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
          defaultResourceFile.statuses.APPROVED ===
            resourceFile.statuses.APPROVED
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
    for (const language of options.languages) {
      await downloadTranslation(language, options.status);
    }

    await panel.view.reload();

    emit("translationUpdate");

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
