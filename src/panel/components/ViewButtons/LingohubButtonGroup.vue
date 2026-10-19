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
const statusError = ref<string>();

const statusButton = computed(() => {
  if (isLoading.value) return { icon: "loader" };

  if (statusError.value) {
    return {
      text: panel.t("johannschopplich.lingohub.status.unavailable"),
      icon: "alert",
      theme: "negative-icon",
    };
  }

  if (isFullyApproved(currentLanguageResourceFile.value)) {
    return {
      text: panel.t("johannschopplich.lingohub.status.approved"),
      icon: "check",
      theme: "positive",
    };
  }

  if (currentLanguageResourceFile.value) {
    return {
      text: panel.t("johannschopplich.lingohub.status.inTranslation"),
      icon: "info",
      theme: "blue-icon",
    };
  }

  return {
    text: panel.t("johannschopplich.lingohub.status.notUploaded"),
    icon: "question",
    theme: "notice-icon",
  };
});

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
    statusError.value = undefined;
  } catch (error) {
    console.error(error);
    // Shown on click, so a page load doesn't raise a notification.
    statusError.value = (error as Error).message;
  } finally {
    isLoading.value = false;
  }
}

function openStatusDialog() {
  if (statusError.value) {
    panel.notification.error(statusError.value);
    return;
  }

  const file = currentLanguageResourceFile.value;
  if (!file) return;

  openFieldsDialog({
    submitButton: panel.t("confirm"),
    fields: {
      languageCode: {
        type: "info",
        label: panel.t("johannschopplich.lingohub.languageCode"),
        text: file.languageCode,
      },
      approved: {
        type: "info",
        theme: "white",
        label: panel.t("johannschopplich.lingohub.status.approved"),
        text: `${file.statuses.APPROVED}`,
      },
      draft: {
        type: "info",
        theme: "white",
        label: panel.t("johannschopplich.lingohub.status.draft"),
        text: `${file.statuses.DRAFT}`,
      },
      notTranslated: {
        type: "info",
        theme: "white",
        label: panel.t("johannschopplich.lingohub.status.notTranslated"),
        text: `${file.statuses.NOT_TRANSLATED}`,
      },
      translated: {
        type: "info",
        theme: "white",
        label: panel.t("johannschopplich.lingohub.status.translated"),
        text: `${file.statuses.TRANSLATED}`,
      },
      failedChecks: {
        type: "info",
        theme: "white",
        label: panel.t("johannschopplich.lingohub.failedChecks"),
        text: `${file.failingLingochecksCount}`,
      },
      lastUpdated: {
        type: "date",
        label: panel.t("johannschopplich.lingohub.lastUpdated"),
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
      :text="statusButton.text"
      :icon="statusButton.icon"
      responsive
      :theme="statusButton.theme"
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
