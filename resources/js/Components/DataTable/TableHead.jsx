import Component from '../Component.jsx';

export default class TableHead extends Component {
  view(vnode) {
    return <thead {...this.attrs.all()}>{vnode.children}</thead>;
  }
}
