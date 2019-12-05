# Xero + AWS - CreditLimits Sample App (deprecated)

## OAuth1.0a deprecation
* Early December 2019 - No new OAuth 1.0a apps created.
* Mid December 2019 - OAuth 2.0 migration endpoint available to partner apps.
* December 2020 - OAuth 1.0a no longer supported for existing integrations.

## How the sample app works

CreditLimits is a basic app for managing and enforcing limits over customer sales invoices. User’s create an account, receive a verification email and login to the App. Under settings menu, the user connects to a Xero organisation and once connected, can load a list of their customers into the app.

A monetary limit can be set against each customer. When new sales invoices are raised a webhook is sent to the app which will then run a check for the value of the invoice against the customer’s limit.

The sales invoices must be saved with the ‘Save and submit for approval’ option. If within the Limit, the app will set the invoice status to ‘Approved and invoke the sending of the invoice via Xero. If the Limit would be exceeded by the new invoice, the app will set the invoice status to ‘Draft’

## How to Setup

Follow these steps to setup your Xero and AWS account and deploy this App.

## Sign up with Xero
Create a [30 day free Xero trial](https://www.xero.com/signup/api/) (your user account never expires) and check out the [demo company](https://central.xero.com/s/article/Use-the-demo-company#Web) you’ll use when connecting to a Xero Org. After you sign up, check your email and activate your account. 

You will need to create an App in the Xero Developer Center. There are three App types, Private, Public and Partner. Webhooks are a key feature of the CreditLimits app, as webhooks cannot be used with the Public app type Private or Partner must be used. The code in the CreditLimits app handles both Private and Partner app types, however Partner apps are granted by Xero typically for the purposes of a certified app listed in the Xero Marketplace. If you already have a Partner app you can use it with the CreditLimits app, otherwise use the Private app type.

To create a Xero Private App login to Xero Developer Center > [My Apps](https://app.xero.com/Application) with your Xero User account. 

Click “New App” and complete required fields
* Use “Private” Type
* Application Name : Your choice
* Organisation : Select an organisation to use, we recommend Demo Company
* Public Key : Follow the steps to create and upload a public key
* Checkbox to agree to T&Cs

After the App is created you’ll have a Consumer Key and Secret, which along with the Private key file created when generating your Public key file, will be used to access the Xero API. Keep your Private key file safe, you’ll need this later.

## Setup Amazon Web Services
Now let’s  sign up for AWS and set up the services needed to run our Sample App.

### Create an AWS User Account
If you already have an AWS account, you can use it. However, we recommend you run our Sample App in a different account from your production workloads. A security best practice is to have separate AWS accounts for production vs sample applications, test, etc.

If you do not already have an AWS account, [sign up for one](https://portal.aws.amazon.com/gp/aws/developer/registration/index.html)

Enter your email address and choose “I am a new user” then click “Sign in using our secure server”:

![alt text](https://github.com/XeroAPI/xdhax-java-sample/blob/master/image/image-3.png?raw=true)

On the next page, fill in the Login Credentials. We recommend you choose a strong password:

![alt text](https://github.com/XeroAPI/xdhax-java-sample/blob/master/image/image-4.png?raw=true)

On the Contact Information page, fill in the details, read the AWS Customer Agreement, and click “Create Account and Continue”

On the Payment Information page, enter your credit card details and click Next. Note that our sample app may incur some small AWS service charges, but will mostly be covered by AWS Free Tier usage.

On the Identity Verification page, enter your phone number; AWS will then display a PIN on the page, call you, and prompt you to enter a PIN via your phone’s keypad.

On the Support Plan page, select **Basic** support and click **Continue**

AWS will send you an email to confirm your sign up. Click the link in the email to complete the sign-up process and log into the [AWS Console](https://us-east-1.console.aws.amazon.com/console/home?region=us-east-1).

### Setup Cognito
You’ll need to setup a Cognito User Pool for your App. Cognito provides **user identity management**, which our example app will use. 

**Login to AWS Console** and at the top right, select the US East (N. Virginia) region. From the Services search for Cognito.

![alt text](https://github.com/XeroAPI/xdhax-java-sample/blob/master/image/image-9.png?raw=true)

* Click **Manage your User Pools** button
* Click **Create a User Pool**
* Enter a **name** for your User Pool (i.e. CreditLimits)
* Click **Review Defaults**

<img src="https://user-images.githubusercontent.com/8154503/41295543-b9bab99a-6e52-11e8-8ff3-63d448f14510.png">

Click Add app client

<img  src="https://user-images.githubusercontent.com/8154503/41295540-b8ce8d68-6e52-11e8-921f-8ce7b61a7a6b.png">

On the next screen click **Add an app client** … again.

Since, this is a server-side app
* **Uncheck** Generate client secret
* **Check** Enable sign-in API for server-based authentication (ADMIN_NO_SRP_AUTH)
* Click **Create app** client button.

<img src="https://user-images.githubusercontent.com/8154503/41295542-b92aaa58-6e52-11e8-9457-eeb1ec46d949.png">

Click **return to pool** details link and click **Create Pool** button

Copy the **Pool Id** and save it for use later.

<img  src="https://user-images.githubusercontent.com/8154503/41295539-b85420e6-6e52-11e8-9dd1-04537688d41f.png">

Under General Settings > **App clients** - copy the **App client id** and save it for use later.

<img src="https://user-images.githubusercontent.com/8154503/41295541-b8fff808-6e52-11e8-8fa5-54d783ec63b7.png">

### DynamoDB
You’ll be using AWS DynamoDB - Amazon’s managed NoSQL database - no configuration is needed to run our sample app as it will dynamically create the required tables.

### Create an Elastic Beanstalk Application
Now we’ll create a web server to host our sample app. 
* From the AWS Services, search for Elastic Beanstalk

<img  src="https://user-images.githubusercontent.com/8154503/41294336-b5a28a3e-6e4f-11e8-95e7-6ee482394aff.png">

* Click Create New Application.
* Enter a Name for your application and click Create.

<img  src="https://user-images.githubusercontent.com/8154503/41294378-d75a41da-6e4f-11e8-8b50-c5f8d95657a6.png">

* If no environments exist, click Create One Now. We will need two environments, one to run our website (called a Web Server environment), and one to run a repeating server task to process sales invoices as and when they are raised (called a Worker environment).
* Firstly we’ll create the website environment, select Web Server environment
* On the Create a new environment screen select PHP as the Platform, and select Sample application (we’ll override this with our code later). Click Create Environment
* The environment will start to spin up. Whilst that’s running let’s create our other environment to run our server task. Click CreditLimits in the top left, and then Actions > Create environment:
<img  src="https://user-images.githubusercontent.com/8154503/41295999-c8b58b0e-6e53-11e8-803b-92951ce31fd4.png">

* This time select Worker environment:
<img  src="https://user-images.githubusercontent.com/8154503/41295998-c884f0e8-6e53-11e8-87f3-4e9d13fdb5e3.png">

* Give the environment a better name, for example:
<img  src="https://user-images.githubusercontent.com/8154503/41295997-c85dd4d6-6e53-11e8-95e1-102b11ec08f0.png">

* As before set the Platform to PHP and select the Sample application for now, and click Create Environment.

* Once Elastic Beanstalk has finished it’s magic you should have two environments:


### Configure Environment Properties for Elastic Beanstalk
We need to inject some environment properties into Elastic Beanstalk for our Sample App to use. From the Elastic Beanstalk dashboard, select your Web Server environment and select Configuration > Configuration > Software > Modify

<img src="https://user-images.githubusercontent.com/8154503/41297017-1b08d396-6e56-11e8-9c64-e0b99532cf88.png">

Scroll down to the Environment Properties. Create the following new properties - see the table below:

|  Property Name  | Property Value                                                                                                     |
|-----------------|--------------------------------------------------------------------------------------------------------------------|
| apptype     | Enter either “private” or “partner” (without quotes) depending on which app type you are using.                                                                                |
| region  | Enter the region from your AWS session, just go to AWS console in your browser and look in the url, you’ll see something like this: https://console.aws.amazon.com/elasticbeanstalk/home?region=us-east-1#/environment/configuration?applicationName=CreditLimits&environmentId=e-ygrvijyfht. The value after region= is what we need, so in this example us-east-1.                                                                               |
| consumer_key   | Your consumer key from the app you created in the Xero Developer Centre                                                                                |
| consumer_secret | Your consumer secret from the app you created in the Xero Developer Centre                                                                                       |
| callback       | elasticbeanstalk URL + /CallbackServlet (i.e. http://creditlimits-env.us-east-1.elasticbeanstalk.com/callback.php). This is shown at the top of your environment management page: <img src="https://user-images.githubusercontent.com/8154503/41297179-7f274916-6e56-11e8-8889-6784f3b670d0.png">                                                                           |
| userpool_id | Your cognito pool id from earlier                                                                                                   |
| client_id       | Your cognito app client id from earlier                                                                                                    |                                                       |
| webhook_key       | The webhook key from Xero Developer Centre for your app. Note this is only relevant if you setup your website as HTTPS and use webhooks, see the Register Webhooks section below.                                                        |

Then, click Save in the bottom right, and then Apply Configuration in the top right.

We need to do the same for our Worker environment. Navigate to the Environment Properties in the same way and add the following:


|  Property Name  | Property Value                                                                                                     |
|-----------------|--------------------------------------------------------------------------------------------------------------------|
| apptype     | Same as above                                                                                |
| region  | Same as above                                                                             |
| consumer_key   | Same as above                                                                                  |
| consumer_secret | Same as above                                                                                         |
| callback       | Same as above                                                                                                      |

### Deploy the Sample App
Clone Github Repo If you haven’t already, clone this Github repo to your local machine. 

Open your terminal and download dependencies with Composer.

	composer update

Next find your private key file you created earlier, it’s likely to be called privatekey.pem. Place a copy of this in project folder under certs, and then the appropriate folder for the app type you are using:

<img src="https://user-images.githubusercontent.com/8154503/41298216-c4ffd866-6e58-11e8-9b46-fb9386b23f99.png">

… note that the filename must be privatekey.pem.

Now we need to zip the project folder. Do this through your UI, or is using a Mac use the command line version. This is required because when zipping through the UI MacOS will add hidden folders to the zip file that will confuse Elastic Beanstalk. Simply use the following command to zip the file and exclude these hidden folder:
```
zip creditlimits.zip -r * .[^.]*
```

### Deploy Zip file to Elastic Beanstalk

Now you’re ready to deploy the sample app.

From the Services menu, select the Elastic Beanstalk. Click on your Application. Click on your Web Server environment and select Upload and Deploy.  Click Upload and Deploy Choose the zip you’ve just created from your computer and enter a version number:

<img src="https://user-images.githubusercontent.com/8154503/41298290-effc5a12-6e58-11e8-995f-08e756c606a1.png">

Next we need to prepare the Worker environment. Upload the same zip file in the same way. 

### Register Webhooks

The sample app uses Webhooks. For webhooks to fire correctly you need to complete the Intent To Receive (ITR) process. This requires specifying your webhook URL and clicking Send ITR. Your webhook URL must be running in HTTPS. This presents an extra task in the setup process because your Elastic Beanstalk environment will be running in HTTP.

Switching to HTTPS requires the following steps. These are high level as it’s not the intention of this sample app to cover how to secure a HTTP site and there are many variations on how to go about this. Appendix 1 details the steps we followed to get our sample app running with our own domain, this steps may be useful but as mentioned there will be variations depending on your domain provider:

* You own domain is required. You can register a domain with GoDaddy, AWS Route 53 etc.
* Generate an SSL certificate for your domain. This can be done via GoDaddy or your chosen provider, or using the AWS Certificate Manager. If you use an external provider you must import the certificate to AWS Certificate Manager.
* Add a Load Balancer to your Elastic Beanstalk Web Server environment and add a listener to port 443 with your certificate stored in AWS Certificate Manager
* Add a CNAME DNS entry in your domain provider’s admin panel to point to your Elastic Beanstalk address 

If you just want to get the sample app running without setting up HTTPS and using webhooks there is a simple manual workaround which will be covered in the next section.

### Testing the sample app
Once the zip file has been successfully deployed to each of your two environments, try out your sample app by pointing your browser to your Elastic Beanstalk URL.

**Log In**
* Create yourself a new user login to your app. Because we’ve implemented AWS Cognito this is all handled smoothly and should be self explanatory
* Once logged in you’ll be taken to the Settings page. If you’re using the Private app type this page will simply confirm the name of the Xero organisation you are connected to. If you’re using the Partner app type click connect to start the oauth flow with Xero.

**Set some limits**
* Go to the Credit Limits page. You’ll see a list of your customer with the option to enter a credit limit for each
* Enter a limit for a customer and click save

**Raise an invoice**
* Login to the Xero Demo Company (or whichever Organisation you’ve connected to the sample app)
* Navigate to Contacts and find a customer you’ve set a limit for
* Edit the email address for this customer to your own email
* Raise a new sales invoice for a customer you’ve set a limit for
* At this point a webhook will fire and shortly the script in your Worker environment will process this

Note; if you didn’t setup webhooks there is a manual process to register the event of raising this invoice. When the webhook fires the responding script in the sample app will simply store the orgid in a table named TentantsToCheck. Manually adding this record will replicate the webhook call. Firstly get your orgid, this is displayed on the Settings page in the sample app. Login to the AWS Console, from the Services menu, select DynamoDB. Click Tables, and then select the TenantsToCheck table. Click the Items tab, Create Item, and add your orgid.

* Navigate to History in the sample app, you should see a record for the invoice you just created. If the invoice amount was within the credit limit it will have been set to a status of Approved in Xero, and you will have an email in your inbox (which would normally be sent to the customer). If the limit was exceeded, the invoice will have been set to a status of Draft in Xero

## Appendix 1: HTTPS

The following is a list of steps we took to get our sample app running with HTTPS and using webhooks. 
* Registered a new domain with AWS Route 53.
* Create a new subdomain. Go to AWS Route 53 > Registered Domains > select your domain > Manage DNS > select your domain > click Create Record Set
* On the right hand side, enter a subdomain name. Change type to A-IPv4.  Change Alias to Yes and enter your Elastic Beanstalk address for your web server
* At this point you can test your subdomain, in a browser enter your subdomain, it should display your sample app
* Go to AWS Certificate Manager, click Get Started under Provision Certifcates
* Select Request a public certificate and click Request a Certificate
* Enter your domain (or subdomain). Click Next
* Select whichever validation method you prefer. Note that if you choose email validation AWS uses whois.com to find the registered email address for the domain. Sometimes this can be admin@yourdomain.com, be mindful that you may not have setup this email address yet. Click through the next screens and complete the verification as required
* Note; if you’re using Route 53 the AWS Certificate Manager simply gives you a button to complete this verification via DNS entry, however it can take 30 mins to propagate
* Next go to Elastic Beanstalk, click on your Web Server and go to Configuration
* Under Capacity click Modify and change the Environment Type to Load Balanced. Leave all default, click Save, and then Apply Configuration > Confirm
* Navigate back to Configuration, and then click Modify under Load Balancer
Click Add Listener, set Listener Port to 443, Listener Protocal to HTTPS, Instance Port to 80, Instance Protocal to HTTP, and choose your domains certificate.
* Click Save, then Apply Configuration
* At this point you can test your subdomain in HTTPS mode, in a browser enter your subdomain with HTTPS prefix, it should display your sample app
* Now our sample app is running under a different url we need to update our Xero callback domain setting. Go to Elastic Beanstalk, click on your Web Server and go to Configuration
* Under Software click Modify, scroll to the bottom and update the callback Environment property to your new url, not forgetting to use https://
* Click Save, then Apply Configuration
* Now we’re running as HTTPS we need to register a webhook. Goto Xero Developer Centre > My Apps. Select your app and click Webhooks
* Create a Webhook for Invoices, add https://<yoursubdomain>/webhook/ for Send notifications to. Copy the webhook key
* Go to Elastic Beanstalk, click on your Web Server environment and go to Configuration
* Under Software click Modify, scroll to the bottom and update the webhook_key Environment property to you webhook key
* Click Save, then Apply Configuration
* Back in Xero Developer Centre, click the Send ITR button for your webhook

## Acknowledgement

Special thanks to [Michael Calcinai](https://github.com/calcinai) for all his work on the [xero-php](https://github.com/calcinai/xero-php) SDK
  

## License

This software is published under the [MIT License](http://en.wikipedia.org/wiki/MIT_License).

	Copyright (c) 2018 Xero Limited

	Permission is hereby granted, free of charge, to any person
	obtaining a copy of this software and associated documentation
	files (the "Software"), to deal in the Software without
	restriction, including without limitation the rights to use,
	copy, modify, merge, publish, distribute, sublicense, and/or sell
	copies of the Software, and to permit persons to whom the
	Software is furnished to do so, subject to the following
	conditions:

	The above copyright notice and this permission notice shall be
	included in all copies or substantial portions of the Software.

	THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
	EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES
	OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
	NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT
	HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY,
	WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
	FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR
	OTHER DEALINGS IN THE SOFTWARE.
