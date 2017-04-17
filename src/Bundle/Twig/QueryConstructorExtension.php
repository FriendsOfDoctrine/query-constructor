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
            'fod_query_constructor' => new \Twig_Function_Method(
                $this,
                'render',
                ['needs_environment' => true]
            ),
        ];
    }

    /**
     * @param \Twig_Environment $environment
     */
    public function render(\Twig_Environment $environment, $options = [], $template = 'QueryConstructorBundle::default.html.twig')
    {
        return $environment->render($template, $options);
    }
}
