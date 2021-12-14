import Page from '../Components/Page.jsx';

export default class Dashboard extends Page {
  view(vnode) {
    return (
      <div>
        <h2>{__('Dashboard')}</h2>
        <p>
          {__('Seleziona una voce dal menu a sinistra')}
        </p>
      </div>
    );
  }
}
