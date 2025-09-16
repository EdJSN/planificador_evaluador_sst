// resources/js/modules/shared/tomselect.js
import TomSelect from "tom-select";

export function initTomSelects(root = document) {
  const nodes = root.querySelectorAll("select.js-tomselect");
  nodes.forEach((el) => {
    if (el.tomselect) return; // evitar doble-init

    // Config por defecto + data-attributes
    const isMultiple = el.multiple;
    const placeholder = el.dataset.tsPlaceholder || el.getAttribute("placeholder") || "";

    // maxItems: "null" (texto) => null real | número | por defecto
    let maxItems;
    if (el.dataset.tsMaxItems !== undefined) {
      maxItems =
        el.dataset.tsMaxItems === "null"
          ? null
          : Number.isNaN(Number(el.dataset.tsMaxItems))
          ? (isMultiple ? null : 1)
          : Number(el.dataset.tsMaxItems);
    } else {
      maxItems = isMultiple ? null : 1;
    }

    // plugins: lista separada por coma o remove_button si multiple
    const plugins = [];
    if (el.dataset.tsPlugins) {
      el.dataset.tsPlugins
        .split(",")
        .map((s) => s.trim())
        .filter(Boolean)
        .forEach((p) => plugins.push(p));
    } else if (isMultiple) {
      plugins.push("remove_button");
    }

    new TomSelect(el, {
      plugins,
      maxItems,
      persist: false,
      placeholder,
      onInitialize() {
        this.control_input.placeholder = this.settings.placeholder;
      },
    });
  });
}
