# Frontend login and user management for Neos CMS.

A Neos CMS package that handles frontend login and user management.

## Installation

The NeosRulez.FrontendLogin package is listed on packagist (https://packagist.org/packages/neosrulez/frontendlogin) - therefore you don't have to include the package in your "repositories" entry any more.

Just add the following line to your require section:

```
"neosrulez/frontendlogin": "*"
```

## Settings.yaml

You can configure everything in Settings.yaml:

```
NeosRulez:
  FrontendLogin:
    passwordReset: true
    adminMail: 'foo@bar.com'
    mail:
      templates:
        registration: 'resource://NeosRulez.FrontendLogin/Private/Templates/Mail/Registration.html'
        newpassword: 'resource://NeosRulez.FrontendLogin/Private/Templates/Mail/Password.html'
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
      countries:
        values:
          'AT':
            label: 'Österreich'
      ...
```

## Author

* E-Mail: mail@patriceckhart.com
* URL: http://www.patriceckhart.com
