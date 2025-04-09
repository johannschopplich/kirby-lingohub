import { isKirby5 } from "kirbyuse";
import LingohubButtonGroup from "../components/ViewButtons/LingohubButtonGroup.vue";

export function legacyViewButtonMixin(Vue) {
  if (isKirby5()) {
    return;
  }

  let buttonComponent;

  Vue.mixin({
    mounted() {
      if (this.$options.name !== "k-header") return;

      const { panel } = window;

      if (!panel.multilang) return;
      if (!panel.view.path.startsWith("pages/")) return;

      const buttonGroup = this.$children.find(
        (child) => child.$options.name === "k-button-group",
      );
      if (!buttonGroup) return;

      const languagesDropdown = buttonGroup.$el.querySelector(
        ".k-languages-dropdown",
      );
      if (!languagesDropdown) return;

      const ButtonConstructor = Vue.extend(LingohubButtonGroup);
      buttonComponent = new ButtonConstructor({ parent: this });
      buttonComponent.$mount();

      languagesDropdown.before(buttonComponent.$el);
    },
    beforeDestroy() {
      if (buttonComponent) {
        buttonComponent.$destroy();
        buttonComponent = undefined;
      }
    },
  });
}
