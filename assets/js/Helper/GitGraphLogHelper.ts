// A commit as the design system's graph log draws it (components/graph-log):
// the graph from the hashes, the row from the rest. The one place git's words
// are turned into what a history shows.

export type GitRefMarker = { label: string; tone: string };

export type GitCommitLike = {
  hash: string;
  parents?: string[];
  subject: string;
  author: string;
  date: string;
  refs?: string[];
};

/**
 * The refs pointing at a commit, as markers: the branch checked out and a
 * detached HEAD in the information tone, tags, local branches, then remote
 * ones grey. Where a remote points by default (`origin/HEAD`) says nothing the
 * remote branch beside it does not, and is left out.
 */
export function gitRefMarkers(refs: string[]): GitRefMarker[] {
  const markers: GitRefMarker[] = [];

  for (const ref of refs) {
    if (ref.startsWith('HEAD -> ')) {
      markers.push({ label: ref.slice('HEAD -> '.length), tone: 'info' });
    } else if (ref === 'HEAD') {
      markers.push({ label: 'HEAD', tone: 'info' });
    } else if (ref.startsWith('tag: ')) {
      markers.push({ label: ref.slice('tag: '.length), tone: 'cat-sunflower' });
    } else if (ref.endsWith('/HEAD')) {
      continue;
    } else if (ref.includes('/')) {
      markers.push({ label: ref, tone: 'neutral' });
    } else {
      markers.push({ label: ref, tone: 'cat-grass' });
    }
  }

  return markers;
}

export function gitGraphLogItem(commit: GitCommitLike) {
  return {
    id: commit.hash,
    parents: commit.parents ?? [],
    title: commit.subject,
    refs: gitRefMarkers(commit.refs ?? []),
    meta: commit.author,
    date: commit.date,
    code: commit.hash.slice(0, 7),
  };
}
