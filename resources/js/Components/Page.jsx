import Component from './Component.jsx';

/**
 * The `Page` component
 *
 * @abstract
 */
export default class Page extends Component {
  page: {
    component: string,
    locale: string,
    props: {...},
    url: string,
    version: string,
    ...
  } = JSON.parse($('#app').attr('data-page'));
}
