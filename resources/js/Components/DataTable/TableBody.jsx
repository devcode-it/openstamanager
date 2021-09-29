import Component from '../Component.jsx';

export default class TableBody extends Component {
  view(vnode) {
    return <tbody {...this.attrs.all()}>{vnode.children}</tbody>;
  }
}
