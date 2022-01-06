import Page from '../Components/Page';

export default class Dashboard extends Page {
  view() {
    return (
      <div>
        <h2>{__('Dashboard')}</h2>
        <p>{__('Seleziona una voce dal menu a sinistra')}</p>
      </div>
    );
  }
}
