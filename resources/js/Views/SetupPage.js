import Page from '../Components/Page';

export default class SetupPage extends Page {
  // eslint-disable-next-line no-unused-vars
  view(vnode) {
    return (
      <>
        <p>{this.__('Hello World!')}</p>
      </>
    );
  }
}
