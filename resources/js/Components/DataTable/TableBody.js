import Component from '../Component';

export default class TableBody extends Component {
  view(vnode) {
    return <tbody {...vnode.attrs}>{vnode.children}</tbody>;
  }
}
