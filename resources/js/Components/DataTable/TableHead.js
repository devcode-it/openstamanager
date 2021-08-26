import Component from '../Component';

export default class TableHead extends Component {
  view(vnode) {
    return <thead {...vnode.attrs}>{vnode.children}</thead>;
  }
}
