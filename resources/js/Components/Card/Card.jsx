import Component from '../Component.jsx';

export default class Card extends Component {
  view(vnode) {
    this.attrs.addClassNames('mdc-card', {
      'mdc-card--outlined': this.attrs.has('outlined')
    });

    return (
      <div {...this.attrs.all()}>
        {vnode.children}
      </div>
    );
  }
}
