<script setup lang="ts">
import type { LingohubResourceFile } from "../../types";
import {
  computed,
  onBeforeUnmount,
  ref,
  useDialog,
  usePanel,
  watch,
} from "kirbyuse";
import { useLingohub } from "../../composables/lingohub";
import { TRANSLATION_UPDATE_EVENT } from "../../constants";
import LingohubDropdownContent from "./LingohubDropdownContent.vue";

const panel = usePanel();
const { openFieldsDialog } = useDialog();
const {
  getModelContext,
  getTranslationStatus,
  findResourceFile,
  isFullyApproved,
} = useLingohub();

const isLoading = ref(true);
const dropdownContent = ref<{ toggle: () => void }>();
const currentLanguageResourceFile = ref<LingohubResourceFile>();
const isApproved = computed(() =>
  isFullyApproved(currentLanguageResourceFile.value),
);

let reloadTimeout: ReturnType<typeof setTimeout> | undefined;

watch(() => [panel.view.path, panel.language.code], loadLingohubData, {
  immediate: true,
});

panel.events.on(TRANSLATION_UPDATE_EVENT, scheduleReload);

onBeforeUnmount(() => {
  panel.events.off(TRANSLATION_UPDATE_EVENT, scheduleReload);
  clearTimeout(reloadTimeout);
});

function scheduleReload() {
  clearTimeout(reloadTimeout);
  // Lingohub needs some time to process the updated translation.
  reloadTimeout = setTimeout(loadLingohubData, 2000);
}

function toggle() {
  dropdownContent.value?.toggle();
}

async function loadLingohubData() {
  isLoading.value = true;

  try {
    const [status, { resourceFiles }] = await Promise.all([
      getTranslationStatus(),
      getModelContext(),
    ]);

    currentLanguageResourceFile.value = findResourceFile(
      status,
      resourceFiles[panel.language.code!],
    );
  } catch (error) {
    console.error(error);
    panel.notification.error((error as Error).message);
  } finally {
    isLoading.value = false;
  }
}

function openStatusDialog() {
  const file = currentLanguageResourceFile.value;
  if (!file) return;

  openFieldsDialog({
    submitButton: panel.t("confirm"),
    fields: {
      languageCode: {
        type: "info",
        label: "Language Code",
        text: file.languageCode,
      },
      approved: {
        type: "info",
        theme: "white",
        label: "Approved",
        text: `${file.statuses.APPROVED}`,
      },
      draft: {
        type: "info",
        theme: "white",
        label: "Draft",
        text: `${file.statuses.DRAFT}`,
      },
      notTranslated: {
        type: "info",
        theme: "white",
        label: "Not Translated",
        text: `${file.statuses.NOT_TRANSLATED}`,
      },
      translated: {
        type: "info",
        theme: "white",
        label: "Translated",
        text: `${file.statuses.TRANSLATED}`,
      },
      failedChecks: {
        type: "info",
        theme: "white",
        label: "Failed Quality Checks",
        text: `${file.failingLingochecksCount}`,
      },
      lastUpdated: {
        type: "date",
        label: "Last Updated At",
        time: false,
        disabled: true,
      },
    },
    value: {
      lastUpdated: file.segmentsUpdatedAt,
    },
  });
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
      @click="openStatusDialog()"
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
