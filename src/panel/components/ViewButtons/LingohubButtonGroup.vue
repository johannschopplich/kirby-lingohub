<script setup>
import { ref, useDialog, usePanel, watch } from "kirbyuse";
import { useLingohub } from "../../composables/lingohub";
import LingohubDropdownContent from "./LingohubDropdownContent.vue";

const panel = usePanel();
const { openFieldsDialog } = useDialog();
const { emitter, getTranslationStatus, getTranslationResourceFile } =
  useLingohub();

const defaultLanguage = panel.languages.find((language) => language.default);

if (!defaultLanguage) {
  panel.notification.error(
    "The default language could not be found. Please make sure to set a default language in your Kirby installation.",
  );
}

const isLoading = ref(true);
const isApproved = ref(false);
const dropdownContent = ref();
const currentLanguageResourceFile = ref();

// Re-fetch Lingohub data when the language or Panel path changes
watch(
  () => [panel.view.path, panel.language.code],
  ([path]) => {
    if (path.startsWith("pages/")) {
      loadLingohubData();
    }
  },
  { immediate: true },
);

emitter.on("translationUpdate", () => {
  // Lingohub needs some time to process the updated translation
  setTimeout(loadLingohubData, 2000);
});

function toggle() {
  dropdownContent.value.toggle();
}

async function loadLingohubData() {
  const status = await getTranslationStatus();

  const defaultLanguageResourceFile = await getTranslationResourceFile(
    status,
    defaultLanguage.code,
  );
  currentLanguageResourceFile.value = await getTranslationResourceFile(
    status,
    panel.language.code,
  );

  isApproved.value =
    defaultLanguageResourceFile && currentLanguageResourceFile.value
      ? currentLanguageResourceFile.value.statuses.APPROVED >=
        defaultLanguageResourceFile.statuses.APPROVED
      : undefined;
  isLoading.value = false;
}
</script>

<template>
  <k-button-group layout="collapsed">
    <k-button
      :text="
        isLoading
          ? undefined
          : isApproved
            ? panel.t('johannschopplich.lingohub.status.approved')
            : currentLanguageResourceFile
              ? panel.t('johannschopplich.lingohub.status.inTranslation')
              : panel.t('johannschopplich.lingohub.status.notUploaded')
      "
      :icon="
        isLoading
          ? 'loader'
          : isApproved
            ? 'check'
            : currentLanguageResourceFile
              ? 'info'
              : 'question'
      "
      responsive
      :theme="
        isLoading
          ? undefined
          : isApproved
            ? 'positive'
            : currentLanguageResourceFile
              ? 'blue-icon'
              : 'notice-icon'
      "
      variant="filled"
      size="sm"
      @click="
        currentLanguageResourceFile &&
        openFieldsDialog({
          submitButton: panel.t('confirm'),
          fields: {
            languageCode: {
              type: 'info',
              label: 'Language Code',
              text: currentLanguageResourceFile.languageCode,
            },
            approved: {
              type: 'info',
              theme: 'white',
              label: 'Approved',
              text: `${currentLanguageResourceFile.statuses.APPROVED}`,
            },
            draft: {
              type: 'info',
              theme: 'white',
              label: 'Draft',
              text: `${currentLanguageResourceFile.statuses.DRAFT}`,
            },
            notTranslated: {
              type: 'info',
              theme: 'white',
              label: 'Not Translated',
              text: `${currentLanguageResourceFile.statuses.NOT_TRANSLATED}`,
            },
            translated: {
              type: 'info',
              theme: 'white',
              label: 'Translated',
              text: `${currentLanguageResourceFile.statuses.TRANSLATED}`,
            },
            failedChecks: {
              type: 'info',
              theme: 'white',
              label: 'Failed Quality Checks',
              text: `${currentLanguageResourceFile.failingLingochecksCount}`,
            },
            lastUpdated: {
              type: 'date',
              label: 'Last Updated At',
              time: false,
              disabled: true,
            },
          },
          value: {
            lastUpdated: currentLanguageResourceFile.segmentsUpdatedAt,
          },
        })
      "
    >
    </k-button>
    <k-button
      :dropdown="true"
      :text="panel.t('johannschopplich.lingohub.label')"
      icon="lingohub-global"
      responsive="text"
      variant="filled"
      size="sm"
      @click="toggle()"
    >
    </k-button>
    <k-dropdown-content ref="dropdownContent">
      <LingohubDropdownContent />
    </k-dropdown-content>
  </k-button-group>
</template>
