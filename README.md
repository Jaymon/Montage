# Montage

Montage is a framework for php with two main goals:

1 - Low Configuration, Montage aims to be as fast as possible to get you up and coding your new project

2 - Easy to extend, Montage aims to get out of your way when you are ready to take your project to the next level.

Montage achieves goal 1 by using convention over configuration, as long as your classes extend the right class, and your 
comments are correct, Montage will route your requests and handle injecting dependencies into your objects.

Montage achieves goal 2 by sticking only to the basics, chances are you already have favorite external libraries (like an Orm)
that you would rather use rather than some random framework's new orm, so Montage doesn't include things like that, it just
handles requests, you are free to use whatever your want for everything else.

So, basically, Montage isn't a full service Framework like Symfony, but a Framework that only handles requests and responses.

Montage is still very beta and is really just a test to see if some of the ideas I've had kicking around my head about my perfect framework are actually practical.

## What's in the box?

Montage contains the following components:

  - Dependency Injection Container
  - Controller
  - Request
  - Response
  - Session
  - Template
  - Url Generator
  - Form
  
and doesn't contain things like:

  - Database ORM
  - Email Handler
  
Since there are popular readily available projects that you are most likely more familiar with and probably want to use instead of some half-baked, half-supported core Framework object that isn't as good.

## How is Montage Low Configuration?

By choosing convention over configuration and using Reflection wherever possible to infer how things should work. I say low configuration because sometime Montage will infer wrong and will need to be properly configured in order to make the right choice.

## How is a Montage App Structured?

A typical Montage app will use the following folder structure:

    AppName/
      src/
      vendor/
      config/
      cache/
      public/
      plugins/
      view/
      data/
  
What do the different folders do:

  - src - where all your custom php files should go.
  - vendor - where any external libraries should go.
  - config - where any configuration your app might need/use should go
  - cache - Montage will try and cache to this directory
  - public - where requests will be handled, this will typically have the index.php file that handles all requests
  - plugins - any plugins you are using, plugin folders are organized more or less the same way as any Montage app
  - view - all your template files
  - data - any data files your app will use

## How Does the Controller Work?

Montage interprets any incoming request using the following guideline:

    domain.com/Controller/Method/params...

So, for example, getting a user's settings might use a url like this:

    domain.com/user/settings/Username

which Montage will interpret as:

    class UserEndpoint extends \Montage\Controller\Endpoint {
    
      public function handleSettings($username){}//method
    
    }//class
    
## How Does the Dependency Container Work?

Using Reflection, Montage will find the absolute child of a class and automatically use that class. Then when an object is created, Montage uses type hinting to inject the instance into the class.

For example, let's say you want to override Montage's Request object to have a dependancy on Foo, just extend Montage's normal request object with your own, and place it in your app's `src/` folder:

    class Request extends \Montage\Request\Request {
    
      public function __construct(\Foo $foo){}//method
    
    }//class
    
Now, when another class wants the Request object it will automatically use your custom Request class instead of the default Montage Request class, and your custom object will automatically have Foo injected, and you didn't have to do anything except create the new class and override the constructor to take a Foo instance.

You can also use anotations to inject dependencies in your class, here is the same example using anotations:

    class Request extends \Montage\Request\Request {

      /*
       * @var \Foo
       */
      public $foo = null;
    
    }//class

## Montage's Requirements

Montage requires php >= 5.3.3 because it uses Closures and namespaces. To run the tests you'll need PHPUnit >= 3.5.0. Also, you'll need a high tolerance for the unknown since there isn't much formal documentation, so you'll have to spend a lot of time looking at the code.

Take a look at the `composer.json` file to see all the dependencies needed, you can use [composer](http://getcomposer.org/) to satisfy the dependencies.

## Todo

There is still tons to do, and I am slowly changing things as I build new projects with Montage, but for the most part, it works and I now consider it stable enough to use on some personal projects of mine.

Here are some of the other things I don't much care for:

- Exception handling is still pretty rough, when an exception is uncaught it just gets printed out and I'm not fully satisfied with how that all works

- There is no full caching in prod, so the cache will always be checked instead of just assumed to be there and complete, I think fixing this will increase prod load times greatly. I think there should be a command line app:

    php montage cache/compile

that can be run to compile the cache on deploy to prod, likewise, if apc is enabled, the cache should be smart enough to remove the cached file from apc when it writes out a new cache file.

- I'm not satisfied with the unit tests at all, there aren't enough, and the ones that are there seem fragile

- In order to override the default page, your `DefaultEndpoint` or `DefaultCommand` have to override the `\Montage\Controller\DefaultEndpoint` or `\Montage\Controller\DefaultCommand` instead of just `\Montage\Controller\Endpoint` like every other controller endpoint has to extend, this is bad because it isn't uniform, and so it will lead to confusion (I was confused for a second) but I'm not sure I have a good fix for this yet that isn't complicated, like checking for an app controller, and only then choosing the Montage versions if no other controller with a non Montage namespace can be found.

## Why did you write Montage when there are so many other awesome frameworks?

Montage started with me wanting to get better at writing code that follows [Misko Hevery's Google code reviewers guide]:(http://misko.hevery.com/code-reviewers-guide/). Then I got another idea, and another, and another. And wouldn't it be awesome if Montage did this... and pretty soon it was a near full framework.

## License

[The MIT License](http://www.opensource.org/licenses/mit-license.php)

