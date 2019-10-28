<?php

namespace Videni\Bundle\RapidGraphQLBundle\GraphQL\Resolver;

use Overblog\GraphQLBundle\Definition\Argument;
use Symfony\Component\HttpFoundation\Request;
use Overblog\GraphQLBundle\Definition\Resolver\ResolverInterface;

class FormSchema implements ResolverInterface
{
    private $resourceContextResolver;
    private $formHandler;

    public function __construct(
        ResourceContextResolver $resourceContextResolver,
        FormHandler $formHandler
    ) {
        $this->resourceContextResolver = $resourceContextResolver;
        $this->formHandler = $formHandler;
    }

    public function __invoke(Argument $args, $operationName, $actionName, Request $request)
    {
        $context = $this->resourceContextResolver->resolveResourceContext($operationName, $actionName);

        $data = $this->resourceContextResolver->resolveResource($args, $context);

        return $this->formHandler->generateFormSchema(
            $data,
            $context,
            $request
        );
    }
}
