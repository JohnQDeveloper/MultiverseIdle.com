# Points of Contact

Codeberg is for humans to open bug reports, discuss changes, etc.

IF you want real human responses contact us at:

**Codeberg**
https://codeberg.org/johnQdeveloper/www.MultiverseIdle.com

OR

**Discord**
https://discord.gg/umagBhVg

_Github is ONLY for testing automated tooling like CodeRabbit that has better support over there._

# Github

Github is for automated reviews of pull request / code / tooling / development work. Basically, the tooling here is just not beatable by open source right now. That said, it has no value to end users.

https://github.com/JohnQDeveloper/MultiverseIdle.com

# Mobile vs. Desktop View

I'm cheating here and just having the phone players scroll a fixed width viewport. It is not ideal but for a hobby/side project I can choose between using a large CSS/JS library to build out stuff (pages are slower to load on mobile) or do something simple like pico/fixed viewports. The inbetween is a bit too time consuming for this project.

You just can't really beat having pages that are 25% the file size of a normal website.

# CSS Library

https://picocss.com/docs

# Composer Packages

https://github.com/delight-im/PHP-Auth?tab=readme-ov-file


# Why open source?

Games are more about community than most people give them credit in combination with the fact this is not my full time job but a hobby/side project.

As such, there really is not a reason to avoid it being open source as it is more "an example of this type of project" as a practical learning exercise.

That said, I do NOT recommend you base your projects around similar design concepts. These are legacy design patterns and when dealing with other developers in a professional setting you should use something they are used to.

# Why not Laravel/Symfony/etc

I like the simplicity of doing things this way. It might be slower and with less magic but there is also less frustration since problems are easy to reason through.

# Why mixing functions and classes and requires instead of MVC?

If I wanted to do MVC, it would be smarter to use Laravel anyway tbh.

But the vanilla, legacy code pattern of:

**Index -> Config -> Router -> load code file -> load view file -> page is done**

is pretty standard at this point in most legacy code. Something like 70-80% of the codebases use this pattern if they are just vanilla PHP.

I honestly like this pattern better than MVC for a couple reasons:

1) The linear logic means its easy to figure out.
2) The forced naming convention of a router that basically grabs X file if X file exists and 404s if not.
3) The error messages don't misidentify problems as probably 50% of programming is just fixing shit that was broken. The other 50% is gathering requirements. The writing of code in between is incidental. It is part of why I don't expect vibe coding to ever be particularly efficient until it can reliably handle break/fix pull requests (part of what I'm also testing in this project) since fixing another programmer's code is going to be just as time consuming as writing it yourself + fixing it anyway. Hopefully by the time I'm replaced by AI it'll be 20-30 years from now and I can safely retire. ;)


Similarly, I'm using a data access layer that essentially acts as a very thin model. (i.e. Converts SQL queries to associative array style data structures) And the code that runs before the view file is largely a controlller in practice.

Practicalities of making stuff simple to reason and make work is more important than dividing stuff up the way a traditional PHP framework would.
