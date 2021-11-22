import Component from '../Component.jsx';

/**
 * Attributes:
 * - type?: numeric, checkbox
 */
export default class TableColumn extends Component {
  view(vnode) {
    this.attrs.addClassNames('mdc-data-table__header-cell', {
      [`mdc-data-table__header-cell--${this.attrs.get('type')}`]: this.attrs.has('type')
    });

    if ((!vnode.children || vnode.children.length === 0) && this.attrs.get('type') === 'checkbox') {
      vnode.children = <mwc-checkbox/>;
    }

    return <th {...this.attrs.all()} role="columnheader" scope="col">{vnode.children}</th>;
  }

  oncreate(vnode) {
    super.oncreate(vnode);

    if (this.attrs.get('type') === 'checkbox') {
      window.vnode = $(vnode.dom);
      const checkbox = $(vnode.dom)
        .children('mwc-checkbox');

      checkbox.on('change', () => {
        $(vnode.dom)
          .closest('table')
          .find('tbody tr[checkable] mwc-checkbox')
          .prop('checked', checkbox.prop('checked'));
      });
    }
  }
}
