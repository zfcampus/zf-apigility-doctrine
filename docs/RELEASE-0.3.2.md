# OAuth2 Support

I am building a Doctrine OAuth2 adapter for zf-oauth2 and support for the zf-oauth2 library is now functional through Query Create Filters and Query Providers.  Each class has an injected OAuth2 Server if a ZF\OAuth2\Service\OAuth2Server is in the service locator.  

To validate an authenticated OAuth2 client has the 'create' scope in a Query Create Filter and inject the user into the data:
```php
namespace RollNApi\Query\CreateFilter;

use ZF\Apigility\Doctrine\Server\Query\CreateFilter\DefaultCreateFilter;
use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use ZF\ApiProblem\ApiProblem;
use ZF\Rest\ResourceEvent;

class UserAlbumCreateFilter extends DefaultCreateFilter
{
    public function filter(ResourceEvent $event, $entityClass, $data)
    {
        $validate = $this->validateOAuth2('create');
        if ($validate instanceof ApiProblem) {
            return $validate;
        }

        $request = $event->getRequest()->getQuery()->toArray();

        $identity = $event->getIdentity()->getAuthenticationIdentity();
        $data->user = $identity['user_id'];

        return $data;
    }
}
```

