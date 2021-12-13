import Page from '../Components/Page.jsx';

export default class Dashboard extends Page {
  view(vnode) {
    return (
      <div>
        <h2>{__('Dashboard')}</h2>
        <p>
          This is the dashboard page.
        </p>
      </div>
    );
  }
}
