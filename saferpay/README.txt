Payment Saferpay testing module scenario.

SaferpayTestController

  createPayInit()
    1. Receive data from Payment Saferpay Form Method.
    2. Generate a payment link with the correct data.
    3. Redirect the user to the payment link.

    Payment Link should be the Saferpay Test Form (test module).

    An example of a request to the payment link should look something like: http://d8/saferpay/success/84?DATA=%3CIDP+MSGTYPE%3d%22PayConfirm%22+TOKEN%3d%22(unused)%22+VTVERIFY%3d%22(obsolete)%22+KEYID%3d%221-0%22+ID%3d%22zzUIU8br3YGdvAx6t13QAC3vt0nA%22+ACCOUNTID%3d%2299867-94913159%22+PROVIDERID%3d%2290%22+PROVIDERNAME%3d%22Saferpay+Test+Card%22+PAYMENTMETHOD%3d%226%22+ORDERID%3d%2284%22+AMOUNT%3d%221000%22+CURRENCY%3d%22CHF%22+IP%3d%2283.150.36.145%22+IPCOUNTRY%3d%22CH%22+CCCOUNTRY%3d%22US%22+MPI_LIABILITYSHIFT%3d%22yes%22+MPI_TX_CAVV%3d%22AAABBIIFmAAAAAAAAAAAAAAAAAA%3d%22+MPI_XID%3d%22VEEeOQJpGzhkBDgbPl0GGBQkHQk%3d%22+ECI%3d%221%22+CAVV%3d%22AAABBIIFmAAAAAAAAAAAAAAAAAA%3d%22+XID%3d%22VEEeOQJpGzhkBDgbPl0GGBQkHQk%3d%22+%2f%3E&SIGNATURE=b71b63c1b42b36484856db5c6a08a2adb5a5bb74ba1db627ab4081ed18dbf81b635b48fc9e4d5707709c2dbe5ee627b654d5e4fcfc6ea34685d09770654ec228

    How the payment link data generation should be done is unclear at this moment.

  verifyPayConfirm
    1. Receive data from Saferpay Response Controller
    2. Verify the data from the response controller.
    3. Callback to the response controller

    In received data you can expect: DATA, SIGNATURE & ACCOUNTID.

    An example of a succesfull callback: OK:ID=56a77rg243asfhmkq3r&TOKEN=%3e235462FA23C4FE4AF65…
    For a unsuccesfull callback return: ERROR: {Possible manipulation}.

    IF and How the verification of the data should take place is unclear at this moment.

  payComplete
    1. Receive data from Saferpay Response Controller.
    2. Verify the data from the response controller.
    3. Call back to the response controller.

    In received data you can expect: ACCOUNTID & ID.

    An example of a succesfull callback: OK
    For an unsuccesfull callback return: ERROR: {Error description}


SaferpayTestForm

  Saferpay Test Form
    1. List all the received data for testing purposes.
    2. After clicking on a Submit button the test should be redirected to the
       success URL which will run the Saferpay Response Controller.

  Don't think we need any form fields in the form.

Extra notes:
1. The submission of the parameter spPassword is specific to the use of the Saferpay test account. On liveaccounts this parameter must not be submitted.
2. Please check the documentation of Saferpay Payment Page on this website:
   https://www.bs-card-service.com/fileadmin/user_upload/com-de/Dokumente/02_CONTENT/02_E-Commerce-Versandhandel/Allpos/BS_allpos_Payment_Page_V43_EN.pdf
