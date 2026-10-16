<script>
import AbstractEntityScrollCollectionVueMixin from '@wexample/symfony-design-system/js/Vue/AbstractEntityScrollCollectionVueMixin';
import buildTranslatedBindings from '@wexample/symfony-design-system/js/Helper/TranslationHelper';
import GraphLog from '@wexample/symfony-design-system/components/graph-log/graph-log.vue';
import LoadMore from '@wexample/symfony-design-system/components/load-more/load-more.vue';
import Spinner from '@wexample/symfony-design-system/components/spinner/spinner.vue';
import Commit from '@wexample/symfony-git/Entity/Commit';
import { gitGraphLogItem } from '../../js/Helper/GitGraphLogHelper';

const translated = buildTranslatedBindings({
  resolvedLoadingLabel: [
    'loadingLabel',
    'WexampleSymfonyDesignSystemBundle.common.system::frontend.loading'
  ],
  resolvedEmptyLabel: [
    'emptyLabel',
    'WexampleSymfonyDesignSystemBundle.common.system::frontend.no_data'
  ],
  resolvedLoadMoreLabel: [
    'loadMoreLabel',
    'WexampleSymfonyGitDsBundle.components.git-graph-log.git-graph-log::load_more'
  ]
});

// A repository's history drawn as a graph (the design system's graph log),
// read from symfony-git's api a page at a time as it is scrolled: the whole of
// it, however old, or the history of one path. The repository is named as the
// api knows it, never by where it stands on disk.
export default {
  template: '#vue-template-wexample-symfony-git-ds-bundle-components-git-graph-log-git-graph-log',

  mixins: [AbstractEntityScrollCollectionVueMixin],

  components: {
    GraphLog,
    LoadMore,
    Spinner
  },

  props: {
    ...translated.props,
    // The repository, by the name the api serves it under.
    repository: {
      type: String,
      required: true
    },
    // A path in it, for its own history; the whole history when empty.
    path: {
      type: String,
      default: ''
    },
    // The topic told when the repository moves, and the event on it —
    // { entityName, id, event } —, after which the history is read again:
    // a commit made anywhere comes in at its head.
    liveSource: {
      type: Object,
      default: null
    }
  },

  data() {
    return {
      // A screen of lines and more: a history is read by the hundred.
      pageLength: 100
    };
  },

  computed: {
    ...translated.computed,

    items() {
      return this.entities.map((commit) => gitGraphLogItem(commit));
    }
  },

  methods: {
    getEntityClass() {
      return Commit;
    },

    getEntitiesFetchParams() {
      return { query: { repository: this.repository, path: this.path } };
    },

    getLiveSource() {
      return this.liveSource;
    }
  }
};
</script>
