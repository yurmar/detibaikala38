/* Блоки «Конкурс рисунков» (без сборки). */
(function (wp, data) {
	'use strict';
	var el = wp.element.createElement, SSR = wp.serverSideRender, PanelBody = wp.components.PanelBody, SelectControl = wp.components.SelectControl;
	var InspectorControls = wp.blockEditor.InspectorControls, useBlockProps = wp.blockEditor.useBlockProps;

	Object.keys(data.blocks).forEach(function (slug) {
		var def = data.blocks[slug], name = 'kids-art-contest/' + slug;
		wp.blocks.registerBlockType(name, {
			apiVersion: 3,
			title: def.title,
			category: 'widgets',
			icon: 'art',
			attributes: { contestId: { type: 'number', default: 0 } },
			edit: function (props) {
				var id = props.attributes.contestId, bp = useBlockProps();
				return el('div', bp,
					el(InspectorControls, null,
						el(PanelBody, { title: 'Конкурс' },
							el(SelectControl, { label: 'Конкурс', value: id, options: data.contests, onChange: function (v) { props.setAttributes({ contestId: parseInt(v, 10) || 0 }); } }))),
					id ? el(SSR, { block: name, attributes: props.attributes })
					   : el('p', null, 'Выберите конкурс в настройках блока справа.'));
			},
			save: function () { return null; }
		});
	});
})(window.wp, window.KACBlocks);
