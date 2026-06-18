(function () {
	var registerFormatType = wp.richText.registerFormatType;
	var applyFormat        = wp.richText.applyFormat;
	var removeFormat       = wp.richText.removeFormat;
	var RichTextToolbarButton = wp.blockEditor.RichTextToolbarButton;
	var Modal    = wp.components.Modal;
	var Button   = wp.components.Button;
	var useState = wp.element.useState;
	var useEffect = wp.element.useEffect;
	var Fragment = wp.element.Fragment;
	var el = wp.element.createElement;

	registerFormatType('deti-baikala/team-link', {
		title: 'Ссылка на сотрудника',
		tagName: 'span',
		className: 'team-link',
		attributes: { id: 'data-id' },
		edit: function (props) {
			var isActive     = props.isActive;
			var value        = props.value;
			var onChange     = props.onChange;

			var visibleState = useState(false);
			var visible      = visibleState[0];
			var setVisible   = visibleState[1];

			var membersState = useState([]);
			var members      = membersState[0];
			var setMembers   = membersState[1];

			useEffect(function () {
				wp.apiFetch({ path: '/wp/v2/team_member?per_page=100&orderby=title&order=asc' })
					.then(function (data) { setMembers(data); })
					.catch(function () {});
			}, []);

			return el(
				Fragment,
				null,
				el(RichTextToolbarButton, {
					icon: 'admin-users',
					title: 'Сотрудник',
					onClick: function () { setVisible(!visible); },
					isActive: isActive,
				}),
				visible && el(
					Modal,
					{ title: 'Выберите сотрудника', onRequestClose: function () { setVisible(false); }, style: { maxWidth: '360px' } },
					members.length === 0
						? el('p', { style: { color: '#888', fontSize: '13px', margin: 0 } }, 'Загрузка...')
						: el('div', null,
							members.map(function (m) {
								return el(Button, {
									key: m.id,
									variant: 'tertiary',
									style: { display: 'block', width: '100%', textAlign: 'left', marginBottom: '4px' },
									onClick: function () {
										onChange(applyFormat(value, {
											type: 'deti-baikala/team-link',
											attributes: { id: String(m.id) },
										}));
										setVisible(false);
									},
								}, m.title.rendered);
							}),
							isActive && el('div', { style: { borderTop: '1px solid #e0e0e0', marginTop: '8px', paddingTop: '8px' } },
								el(Button, {
									variant: 'tertiary',
									isDestructive: true,
									style: { display: 'block', width: '100%', textAlign: 'left' },
									onClick: function () {
										onChange(removeFormat(value, 'deti-baikala/team-link'));
										setVisible(false);
									},
								}, 'Убрать ссылку')
							)
						)
				)
			);
		},
	});
})();
