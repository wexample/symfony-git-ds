<?php

namespace Wexample\SymfonyGitDs\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;
use Wexample\SymfonyGit\Class\GitActivity;
use Wexample\SymfonyGit\Service\GitActivityService;

/**
 * `git_activity()`: the work of the last year in the repositories the site
 * knows (GitActivityService), for a page to print — `.repositories`,
 * `.commits`. Its days are drawn by `chart('calendar', {}, { source:
 * 'git-activity' })`, read from the api.
 */
class GitActivityExtension extends AbstractExtension
{
    public function __construct(
        private readonly GitActivityService $activity,
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('git_activity', $this->gitActivity(...)),
        ];
    }

    public function gitActivity(): GitActivity
    {
        return $this->activity->getActivity();
    }
}
