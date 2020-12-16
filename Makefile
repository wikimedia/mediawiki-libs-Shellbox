Dockerfile.dev:
	curl -H 'Content-Type: application/yaml' -X POST  --data-binary @.pipeline/blubber.yaml https://blubberoid.wikimedia.org/v1/dev > Dockerfile.dev

Dockerfile.test:
	curl -H 'Content-Type: application/yaml' -X POST  --data-binary @.pipeline/blubber.yaml https://blubberoid.wikimedia.org/v1/test > Dockerfile.test


.devimage: Dockerfile.dev
	docker build . -f Dockerfile.dev -t shellbox-dev:local
	touch .devimage

.testimage: Dockerfile.test
	docker build . -f Dockerfile.test -t shellbox-test:local
	touch .testimage

test: .testimage
	docker run -v $(CURDIR)/src:/srv/app/src --rm shellbox-test:local

run: Dockerfile.dev
	docker-compose up

rebuild: Dockerfile.dev
	docker-compose up --build

clean:
	-rm Dockerfile*
	-rm .testimage .devimage
	-docker-compose rm -fsv

.PHONY: clean run test