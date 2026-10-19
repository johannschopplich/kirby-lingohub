import { usePanel } from "kirbyuse";

interface ModelData {
  id?: string;
  blueprint: { name: string };
}

const modelDataCache = new Map<string, ModelData>();
let isListenerRegistered = false;

export function useModel() {
  const panel = usePanel();
  const defaultLanguage = panel.languages.find((language) => language.default);

  // `useModel()` runs per component, but cache and invalidation listener are module-global.
  if (!isListenerRegistered) {
    panel.events.on("page.changeSlug", clearModelData);
    isListenerRegistered = true;
  }

  async function getModelData(): Promise<ModelData> {
    const { path: id } = panel.view;

    if (modelDataCache.has(id)) {
      return modelDataCache.get(id)!;
    }

    const response: ModelData = await panel.api.get(
      id,
      {
        select: ["id", "blueprint"],
        language: defaultLanguage?.code,
      },
      undefined,
      // Avoid showing the Panel loading indicator.
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
