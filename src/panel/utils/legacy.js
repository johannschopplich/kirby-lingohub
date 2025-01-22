import { isKirby5 } from "kirbyuse";
import LingohubButtonGroup from "../components/ViewButtons/LingohubButtonGroup.vue";

export function legacyViewButtonMixin(Vue) {
  if (isKirby5()) {
    return;
  }

  Vue.mixin({
    mounted() {
      if (this.$options.name !== "k-header") return;

      const { panel } = window;

      if (!panel.multilang) return;
      if (panel.view.path === "site") return;

      const buttonGroup = this.$children.find(
        (child) => child.$options.name === "k-button-group",
      );
      if (!buttonGroup) return;

      const button = new Vue({
        render: (h) => h(LingohubButtonGroup),
      }).$mount();

      const languagesDropdown = buttonGroup.$el.querySelector(
        ".k-languages-dropdown",
      );
      if (!languagesDropdown) return;

      languagesDropdown.before(button.$el);
      this.$forceUpdate();
    },
  });
}
