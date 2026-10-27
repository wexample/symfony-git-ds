<?php

namespace Wexample\SymfonyGitDs\Twig;

use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Environment;
use Twig\TwigFunction;
use Wexample\SymfonyDesignSystem\Twig\AbstractTemplateExtension;
use Wexample\SymfonyGit\Class\GitActivity;
use Wexample\SymfonyGit\Service\GitActivityService;
use Wexample\SymfonyGitDs\Helper\GitActivityCalendarHelper;
use Wexample\SymfonyLoader\Twig\ComponentsExtension;

/**
 * - `git_activity()`: the work of the last year in the repositories the site
 *   knows (GitActivityService), for a page to print — `.repositories`,
 *   `.commits`.
 * - `git_activity_calendar({ class })`: its days as a contribution calendar,
 *   drawn on the server, standing up where its place is too narrow.
 */
class GitActivityExtension extends AbstractTemplateExtension
{
    public function __construct(
        ComponentsExtension $componentsExtension,
        private readonly GitActivityService $activity,
        private readonly RequestStack $requestStack,
    ) {
        parent::__construct($componentsExtension);
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('git_activity', $this->gitActivity(...)),
            new TwigFunction(
                'git_activity_calendar',
                function (Environment $twig, $context, array $options = []): string {
                    $activity = $this->gitActivity();
                    $locale = $this->requestStack->getCurrentRequest()?->getLocale() ?? 'en';

                    return $this->renderComponent(
                        $twig,
                        $context,
                        '@WexampleSymfonyGitDsBundle/components/git-activity-calendar',
                        [
                            'class' => $options['class'] ?? null,
                            'commits' => $activity->getCommits(),
                            'cell_size' => GitActivityCalendarHelper::CELL,
                            'layouts' => [
                                'lying' => GitActivityCalendarHelper::layout($activity->days, $locale),
                                'standing' => GitActivityCalendarHelper::layout($activity->days, $locale, true),
                            ],
                        ]
                    );
                },
                self::TEMPLATE_FUNCTION_OPTIONS
            ),
        ];
    }

    public function gitActivity(): GitActivity
    {
        return $this->activity->getActivity();
    }
}
