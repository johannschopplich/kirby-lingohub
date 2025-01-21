<script>
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

<script setup>
const props = defineProps(propsDefinition);

const panel = usePanel();
const { t } = useI18n();
const { getTranslationStatus, getTranslationResources } = useLingohub();

// Section state
const label = ref();

// Runtime state
const resourceFiles = ref();

const tableColumns = {
  languageCode: { label: "Language Code", type: "text" },
  approved: { label: "Approved", type: "number" },
  draft: { label: "Draft", type: "number" },
  notTranslated: { label: "Not translated", type: "number" },
  translated: { label: "Translated", type: "number" },
  failedChecks: { label: "Failed Quality Checks", type: "number" },
  lastUpdated: { label: "Last updated at", type: "date" },
};
const tableRows = computed(() =>
  (resourceFiles.value ?? []).map((item) => ({
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

  const status = await getTranslationStatus();

  resourceFiles.value = await getTranslationResources(
    status,
    panel.language.code,
  );
})();
</script>

<template>
  <k-section :label="label">
    <k-box theme="none">
      <k-table :columns="tableColumns" :rows="tableRows" />
    </k-box>
  </k-section>
</template>
