(function (wp) {
	var registerBlockType = wp.blocks.registerBlockType;
	var el = wp.element.createElement;
	var Fragment = wp.element.Fragment;
	var InspectorControls = wp.blockEditor.InspectorControls;
	var useBlockProps = wp.blockEditor.useBlockProps;
	var PanelBody = wp.components.PanelBody;
	var TextControl = wp.components.TextControl;
	var ServerSideRender = wp.serverSideRender;
	var __ = wp.i18n.__;

	registerBlockType('ndu/today-date', {
		edit: function (props) {
			var attributes = props.attributes;
			var setAttributes = props.setAttributes;
			var blockProps = useBlockProps({ className: 'ndu-today-date-block-editor' });

			return el(
				Fragment,
				null,
				el(
					InspectorControls,
					null,
					el(
						PanelBody,
						{ title: __('Date format', 'nepali-date-utilities'), initialOpen: true },
						el(TextControl, {
							label: __('Format', 'nepali-date-utilities'),
							help: __('Tokens: y m d l H/h i s a. Leave empty to use plugin settings.', 'nepali-date-utilities'),
							value: attributes.format || '',
							onChange: function (value) {
								setAttributes({ format: value });
							}
						})
					)
				),
				el(
					'div',
					blockProps,
					el(ServerSideRender, {
						block: 'ndu/today-date',
						attributes: attributes
					})
				)
			);
		},
		save: function () {
			return null;
		}
	});
})(window.wp);
