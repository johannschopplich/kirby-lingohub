<script lang="ts">
import type { LingohubResourceFile } from "../../types";
import { computed, ref, useI18n, usePanel, useSection } from "kirbyuse";
import { section } from "kirbyuse/props";
import { useLingohub } from "../../composables/lingohub";

const propsDefinition = {
  ...section,
};

export default {
  inheritAttrs: false,
};
</script>

<script setup lang="ts">
const props = defineProps(propsDefinition);

const panel = usePanel();
const { t } = useI18n();
const { getModelContext, getTranslationStatus, findResourceFiles } =
  useLingohub();

// Section state
const label = ref<string>();

// Runtime state
const languageFiles = ref<LingohubResourceFile[]>();
const statusError = ref<string>();

const tableColumns = {
  languageCode: {
    label: panel.t("johannschopplich.lingohub.languageCode"),
    type: "text",
  },
  approved: {
    label: panel.t("johannschopplich.lingohub.status.approved"),
    type: "number",
  },
  draft: {
    label: panel.t("johannschopplich.lingohub.status.draft"),
    type: "number",
  },
  notTranslated: {
    label: panel.t("johannschopplich.lingohub.status.notTranslated"),
    type: "number",
  },
  translated: {
    label: panel.t("johannschopplich.lingohub.status.translated"),
    type: "number",
  },
  failedChecks: {
    label: panel.t("johannschopplich.lingohub.failedChecks"),
    type: "number",
  },
  lastUpdated: {
    label: panel.t("johannschopplich.lingohub.lastUpdated"),
    type: "date",
  },
};
const tableRows = computed(() =>
  (languageFiles.value ?? []).map((item) => ({
    languageCode: item.languageCode,
    approved: item.statuses.APPROVED,
    draft: item.statuses.DRAFT,
    notTranslated: item.statuses.NOT_TRANSLATED,
    translated: item.statuses.TRANSLATED,
    failedChecks: item.failingLingochecksCount,
    lastUpdated: item.segmentsUpdatedAt,
  })),
);

(async () => {
  const { load } = useSection();
  const sectionProps = await load({
    parent: props.parent,
    name: props.name,
  });

  label.value =
    t(sectionProps.label) || panel.t("johannschopplich.lingohub.statusLabel");

  try {
    const [status, { resourceFiles }] = await Promise.all([
      getTranslationStatus(),
      getModelContext(),
    ]);

    languageFiles.value = findResourceFiles(
      status,
      resourceFiles[panel.language.code!],
    );
  } catch (error) {
    console.error(error);
    statusError.value = (error as Error).message;
  }
})();
</script>

<template>
  <k-section :label="label">
    <k-box
      v-if="statusError"
      theme="negative"
      icon="alert"
      :text="statusError"
    />
    <k-box v-else theme="none">
      <k-table :columns="tableColumns" :rows="tableRows" />
    </k-box>
  </k-section>
</template>
