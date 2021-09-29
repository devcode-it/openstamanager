import Component from '../Component.jsx';

export default class TableFooter extends Component {
  view(vnode) {
    return <tfoot {...this.attrs.all()}>{vnode.children}</tfoot>;
  }
}
