import { usePanel } from "kirbyuse";

const modelDataCache = new Map();
let isListenerRegistered = false;

export function useModel() {
  const panel = usePanel();
  const defaultLanguage = panel.languages.find((language) => language.default);

  // Ensure event listener is only set once
  if (!isListenerRegistered) {
    panel.events.on("page.changeSlug", clearModelData);
    isListenerRegistered = true;
  }

  async function getModelData() {
    const { path: id } = panel.view;

    if (modelDataCache.has(id)) {
      return modelDataCache.get(id);
    }

    const response = await panel.api.get(
      id,
      {
        select: ["id", "blueprint"],
        language: defaultLanguage?.code,
      },
      undefined,
      // Avoid showing Panel loading indicator
      true,
    );

    modelDataCache.set(id, response);
    return response;
  }

  function clearModelData() {
    modelDataCache.delete(panel.view.path);
  }

  return {
    getModelData,
    clearModelData,
  };
}
