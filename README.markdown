# Montage

Montage is a near zero configuration Request Framework for PHP containing only the necessary pieces to get you up an running fast while being flexible with external libraries you want to use and being easy to extend and override in the future.

Montage is still very beta and is really just a test to see if the ideas I've had kicking around my head about my perfect framework are actually practical.

## What is a Request Framework?

A Request Framework is a MVC that only handles the request and response portion of a typical framework and so the framework only contains the components to make that specific part work. 

Basically, Montage contains the following components:

  - Dependency Injection Container
  - Controller
  - Request Object
  - Response Object
  - Session Object
  - Template Object
  - Url Generator
  - Form Framework
  
But doesn't contain things like:

  - Database ORM
  - Email Handler
  
Since there are popular readily available projects that you are most likely more familiar with and probably want to use instead of some half-baked, half-supported core Framework object that isn't as good.

## How is Montage Near Zero Configuration?

By choosing convention over configuration and using Reflection wherever possible to infer how things should work. I say near zero because sometime Montage will infer wrong and will need to be configured in order to make the right choice.

## How is a Montage App Structured?

A typical Montage app will use the following folder structure:

    AppName/
      src/
      vendor/
      config/
      cache/
      web/
      plugins/
  
What do the different folders do:

  - src - where all your custom php files should go.
  - vendor - where any external libraries should go.
  - config - where any configuration your app might need/use should go
  - cache - Montage will try and cache to this directory
  - web - where requests will be handled, this will typically have the index.php file
  - plugins - any plugins you are using, plugin folders are organized more or less the same way

## How Does the Controller Work?

Montage interprets any incoming request using the following guideline:

    domain.com/Controller/Method/params...

So, for example, getting a user's settings might use a url like this:

    domain.com/user/settings/Username

which Montage will interpret as:

    class UserController extends \Montage\Controller\Controller {
    
      public function handleSettings($username){}//method
    
    }//class
    
## How Does the Dependency Container Work?

Using Reflection, Montage will find the absolute child of a class and automatically use that class. Then when an object is created, Montage uses type hinting to inject the instance into the class.

For example, let's say you want to override Montage's Request object to have a dependancy on Foo, just extend Montage's normal request object with your own, and place it in your app's src/ folder:

    class Request extends \Montage\Request\Request {
    
      public function __construct(\Foo $foo){}//method
    
    }//class
    
Now, when another class wants the Request object it will automatically get your custom Request object, and your custom object will automatically have Foo injected, and you didn't have to do anything except create the new class and override the constructor to take a Foo instance.

## Using Montage

Montage requires php >= 5.3 because it uses Closures and namespaces. To run the tests you'll need PHPUnit >= 3.5.0. Also, you'll need a high tolerance for the unknown since there isn't any documentation, so you'll have to spend a lot of time looking at the code.

## License

[The MIT License](http://www.opensource.org/licenses/mit-license.php)