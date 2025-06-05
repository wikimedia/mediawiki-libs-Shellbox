test:
	docker buildx build . -f .pipeline/blubber.yaml --target test81 -t shellbox-test:local
	docker run -v $(CURDIR)/src:/srv/app/src -v $(CURDIR)/tests:/srv/app/tests --rm shellbox-test:local

run:
	docker-compose up

rebuild:
	docker-compose up --build

clean:
	-docker-compose rm -fsv

.PHONY: clean run rebuild test
