## The work in figures

`git_activity()` returns the `GitActivity` of the last year (symfony-git): `.repositories`, `.commits`, for a page to print. Its days are drawn by the calendar of symfony-charts-ds, read from the api:

```twig
{%- set activity = git_activity() -%}
{{ activity.repositories }} · {{ activity.commits }}
{{ chart('calendar', {}, { source: 'git-activity', height: '9rem' }) }}
```
