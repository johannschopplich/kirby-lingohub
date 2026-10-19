/* eslint-disable ts/ban-ts-comment */
// @ts-ignore - Vue component
import LingohubStatus from "./components/Sections/LingohubStatus.vue";
// @ts-ignore - Vue component
import LingohubButtonGroup from "./components/ViewButtons/LingohubButtonGroup.vue";
import { icons } from "./config/icons";
import { legacyViewButtonMixin } from "./utils/legacy";

window.panel.plugin("johannschopplich/lingohub", {
  sections: {
    "lingohub-status": LingohubStatus,
  },
  viewButtons: {
    lingohub: LingohubButtonGroup,
  },
  icons,
  use: [legacyViewButtonMixin],
});
