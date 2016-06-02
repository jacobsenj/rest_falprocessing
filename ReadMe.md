ext:FalProcessing

* Setup a fresh TYPO3 7.6 LTS
* Install ext:rest
* Install ext:rest_falprocessing
* Include Static Template
* Upload some images in FAL
* Call your rest api to get original image or processed images

Example-Calls:

http://your.domain/rest/image/resize.json?demand[uid]=15
http://your.domain/rest/image/resize.json?demand[uid]=15&demand[width]=150
http://your.domain/rest/image/resize.json?demand[uid]=15&demand[height]=200
