import type { ComponentPublicInstance, PluginFunction } from "vue";
import { isKirby5 } from "kirbyuse";
import LingohubButtonGroup from "../components/ViewButtons/LingohubButtonGroup.vue";

export const legacyViewButtonMixin: PluginFunction<any> = (Vue) => {
  if (isKirby5()) {
    return;
  }

  let buttonComponent: ComponentPublicInstance | undefined;

  Vue.mixin({
    mounted(this: ComponentPublicInstance) {
      if (this.$options.name !== "k-header") return;

      const { panel } = window;

      if (!panel.multilang) return;
      if (!panel.view.path.startsWith("pages/")) return;

      const buttonGroup = this.$children.find(
        (child: ComponentPublicInstance) =>
          child.$options.name === "k-button-group",
      );
      if (!buttonGroup) return;

      const languagesDropdown = buttonGroup.$el.querySelector(
        ".k-languages-dropdown",
      );
      if (!languagesDropdown) return;

      const ButtonConstructor = Vue.extend(LingohubButtonGroup);
      buttonComponent = new ButtonConstructor({ parent: this as any });
      buttonComponent.$mount();

      languagesDropdown.before(buttonComponent.$el);
    },
    beforeDestroy(this: ComponentPublicInstance) {
      if (this.$options.name !== "k-header") return;
      if (!window.panel.multilang) return;

      if (buttonComponent) {
        buttonComponent.$destroy();
        buttonComponent = undefined;
      }
    },
  });
};
