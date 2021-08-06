import Component from '../Component';

export default class Row extends Component {
  view(vnode) {
    return <div {...vnode.attrs} class={`mdc-layout-grid__inner ${vnode.attrs.class}`}>{vnode.children}</div>;
  }
}
