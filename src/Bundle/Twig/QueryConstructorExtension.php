<?php

namespace FOD\QueryConstructor\Bundle\Twig;

/**
 * Description of QueryConstructorExtension
 *
 * @author Nikita Pushkov
 */
class QueryConstructorExtension extends \Twig_Extension
{
    public function getFunctions()
    {
        return [
            'fod_query_constructor' => new \Twig_SimpleFunction(
                'fod_query_constructor',
                [$this, 'render'],
                ['needs_environment' => true]
            ),
        ];
    }

    /**
     * @param \Twig_Environment $environment
     * @param array $options
     * @param string $template
     * @return string
     */
    public function render(\Twig_Environment $environment, $options = [], $template = 'QueryConstructorBundle::default.html.twig')
    {
        return $environment->render($template, $options);
    }
}
