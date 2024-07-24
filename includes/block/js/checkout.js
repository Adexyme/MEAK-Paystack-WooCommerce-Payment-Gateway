const meak_paystack_settings = window.wc.wcSettings.getSetting(
  "meak_paystack_gateway_data",
  {}
);
const meak_paystack_label =
  window.wp.htmlEntities.decodeEntities(meak_paystack_settings.title) ||
  window.wp.i18n.__("Meak Paystack Gateway", "meak-paystack-gateway");
const meak_paystack_Content = () => {
  return window.wp.htmlEntities.decodeEntities(
    meak_paystack_settings.description || ""
  );
};
const Meak_Paystack_Block_Gateway = {
  name: "meak_paystack_gateway",
  label: meak_paystack_label,
  content: Object(window.wp.element.createElement)(meak_paystack_Content, null),
  edit: Object(window.wp.element.createElement)(meak_paystack_Content, null),
  canMakePayment: () => true,
  ariaLabel: meak_paystack_label,
  supports: {
    features: meak_paystack_settings.supports,
  },
};
window.wc.wcBlocksRegistry.registerPaymentMethod(Meak_Paystack_Block_Gateway);
