import Component from '../Component';

export default class TableHead extends Component {
  view(vnode) {
    return <thead {...this.attrs.all()}>{vnode.children}</thead>;
  }
}
