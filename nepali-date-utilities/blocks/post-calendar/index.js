(function (wp) {
	var registerBlockType = wp.blocks.registerBlockType;
	var el = wp.element.createElement;
	var Fragment = wp.element.Fragment;
	var InspectorControls = wp.blockEditor.InspectorControls;
	var PanelColorSettings = wp.blockEditor.PanelColorSettings;
	var useBlockProps = wp.blockEditor.useBlockProps;
	var PanelBody = wp.components.PanelBody;
	var ToggleControl = wp.components.ToggleControl;
	var SelectControl = wp.components.SelectControl;
	var RangeControl = wp.components.RangeControl;
	var ServerSideRender = wp.serverSideRender;
	var __ = wp.i18n.__;

	var monthOptions = [
		{ label: __('Current month', 'nepali-date-utilities'), value: 0 },
		{ label: '1 — Baisakh / बैशाख', value: 1 },
		{ label: '2 — Jestha / जेष्ठ', value: 2 },
		{ label: '3 — Ashadh / असार', value: 3 },
		{ label: '4 — Shrawan / श्रावण', value: 4 },
		{ label: '5 — Bhadra / भाद्र', value: 5 },
		{ label: '6 — Ashwin / आश्विन', value: 6 },
		{ label: '7 — Kartik / कार्तिक', value: 7 },
		{ label: '8 — Mangsir / मंसिर', value: 8 },
		{ label: '9 — Poush / पुष', value: 9 },
		{ label: '10 — Magh / माघ', value: 10 },
		{ label: '11 — Falgun / फाल्गुन', value: 11 },
		{ label: '12 — Chaitra / चैत्र', value: 12 }
	];

	registerBlockType('ndu/post-calendar', {
		edit: function (props) {
			var attributes = props.attributes;
			var setAttributes = props.setAttributes;
			var blockProps = useBlockProps({ className: 'ndu-post-calendar-block-editor' });

			var yearValue = typeof attributes.year === 'number' ? attributes.year : 0;
			var monthValue = typeof attributes.month === 'number' ? attributes.month : 0;

			var ssrAttributes = {
				lang: attributes.lang || 'nepali',
				showPostLinks: attributes.showPostLinks !== false,
				accentColor: attributes.accentColor || '#c62828'
			};
			if (yearValue > 0) {
				ssrAttributes.year = yearValue;
			}
			if (monthValue > 0) {
				ssrAttributes.month = monthValue;
			}

			return el(
				Fragment,
				null,
				el(
					InspectorControls,
					null,
					el(
						PanelBody,
						{ title: __('Calendar settings', 'nepali-date-utilities'), initialOpen: true },
						el(RangeControl, {
							label: __('BS year (0 = current)', 'nepali-date-utilities'),
							value: yearValue,
							onChange: function (value) {
								setAttributes({ year: value || undefined });
							},
							min: 0,
							max: 2099,
							allowReset: true
						}),
						el(SelectControl, {
							label: __('BS month', 'nepali-date-utilities'),
							value: monthValue,
							options: monthOptions,
							onChange: function (value) {
								var n = parseInt(value, 10);
								setAttributes({ month: n > 0 ? n : undefined });
							}
						}),
						el(SelectControl, {
							label: __('Language', 'nepali-date-utilities'),
							value: attributes.lang || 'nepali',
							options: [
								{ label: __('Nepali', 'nepali-date-utilities'), value: 'nepali' },
								{ label: __('English', 'nepali-date-utilities'), value: 'english' }
							],
							onChange: function (value) {
								setAttributes({ lang: value });
							}
						}),
						el(ToggleControl, {
							label: __('Show post links', 'nepali-date-utilities'),
							checked: attributes.showPostLinks !== false,
							onChange: function (value) {
								setAttributes({ showPostLinks: value });
							}
						})
					),
					el(PanelColorSettings, {
						title: __('Accent color', 'nepali-date-utilities'),
						colorSettings: [
							{
								value: attributes.accentColor || '#c62828',
								onChange: function (value) {
									setAttributes({ accentColor: value || '#c62828' });
								},
								label: __('Accent', 'nepali-date-utilities')
							}
						]
					})
				),
				el(
					'div',
					blockProps,
					el(ServerSideRender, {
						block: 'ndu/post-calendar',
						attributes: ssrAttributes
					})
				)
			);
		},
		save: function () {
			return null;
		}
	});
})(window.wp);
