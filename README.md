# Why open source?

Games are more about community than most people give them credit in combination with the fact this is not my full time job but a side hustle for underlying engineering projects that are more serious financially. (i.e. Multidatacenter failover testing, testing deployment processes, etc)

As such, there really is not a reason to avoid it being open source as it is more "an example of this type of project" as a practical learning exercise.

That said, I do NOT recommend you base your projects around similar design concepts. These are legacy design patterns and when dealing with other developers in a professional setting you should use something they are used to.

# Why not Laravel/Symfony/etc

40% of my career is cleaning up legacy projects written without a framework and at most a handful of composer libraries. Staying in pratice in this regard is useful.

There is also the practical problems with frameworks in modern PHP:

1) Overeliance on ORMs that quietly break only in production via performance issues that are opaque requiring hours to fix.

2) Upgrades are major projects between major versions of frameworks. I just don't really have time for this.

3) Similarly to ORMs, "magic" heavy frameworks like Laravel have a tendency towards opaque bug messages that aren't exactly what is broken. Sure, it might only happen a few times a year on a project like this, but that is development time I can't get back.

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
