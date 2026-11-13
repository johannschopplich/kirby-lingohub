import LingohubStatus from "./components/Sections/LingohubStatus.vue";
import LingohubButtonGroup from "./components/ViewButtons/LingohubButtonGroup.vue";
import { icons } from "./config/icons";

window.panel.plugin("johannschopplich/lingohub", {
  sections: {
    "lingohub-status": LingohubStatus,
  },
  viewButtons: {
    lingohub: LingohubButtonGroup,
  },
  icons,
});
