# Tansarc_EmailTemplateAdapter
Status: Deprecated

This is a fork from [Tansarc_EmailTemplateAdapter](http://www.magentocommerce.com/magento-connect/email-template-adapter.html)
that adds the following functionalities:

## Added functionality

### 1. Fixed a bug where transactional emails created in the admin panel didn't get parsed.

### 2. Extended it so it gives a little more options to style html elements:
![Alt text](http://i.imgur.com/43TPm.jpg)

### 3. Added extra templatingoptions for in your emailtemplates

Before you could do:
`{{if billing.getFirstName()}}Congratulations on having a first name{{else}}Ahhhh too bad{{/if}}`
Now you can do:
`{{if billing.getFirstName() == john}}Hi John!{{else}}You're not John :({{/if}}`

Before you could do:
`{{depend billing.getFirstName()}}Congratulations on having a first name{{/depend}}`
Now you can do:
`{{depend billing.getFirstName() == john}}Hi John!{{/depend}}`

We've added a new configFlag. This allows us to check if certail variables are filled in:
`{{configFlag general/store_information/phone}}Het telefoonnummer is: {{config path='general/store_information/phone'}}{{/configFlag}}`
And:
`{{configFlag general/store_information/phone == '123123'}}OK COOL!{{/configFlag}}`

This added functionality also works in all other classes that extend `Mage_Core_Model_Email_Template_Filter` like CMS pages,
descriptions, etc., so thats pretty cool if you ask me.

This module was added for the optimzed dutch email templates: https://github.com/ho-nl/magento-nl_NL

## How can you help?

- We need to add a good default config. Currently the module needs to be heavily configured to get it working. Would be
better if the module just worked and only the things that you wanted to be tweaked needs to be tweaked instead of the
entire system.

- Give us some awesome ideas for more cool styling, additional features etc.
