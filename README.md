# Frontend login and user management for Neos CMS.

A Neos CMS frontend login and user management package with a powerful and "Neos policy independent" permission management.

## Installation

The NeosRulez.FrontendLogin package is listed on packagist (https://packagist.org/packages/neosrulez/frontendlogin) - therefore you don't have to include the package in your "repositories" entry any more.

Just run:

```
composer require neosrulez/bootstrap
```

## Settings.yaml

You can configure everything in Settings.yaml:

```yaml
NeosRulez:
  FrontendLogin:
    passwordReset: true
    adminMail: 'foo@bar.com'
    mail:
      templates:
        registration: 'resource://NeosRulez.FrontendLogin/Private/Templates/Mail/Registration.html'
        resetPassword: 'resource://NeosRulez.FrontendLogin/Private/Templates/Mail/ResetPassword.html'
    registration:
      autoActive: true
      defaultRole: 'NeosRulez.FrontendLogin:FrontendUser'
      formfields:
        salutation:
          required: true
        company:
          required: false
        name:
          required: true
        address:
          required: true
        zipcity:
          required: true
        country:
          required: true
          default: 'AT'
        phone:
          required: false
        email:
          required: true
        privacy:
          required: true
```

## Author

* E-Mail: mail@patriceckhart.com
* URL: http://www.patriceckhart.com
